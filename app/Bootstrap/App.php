<?php

namespace App\Bootstrap;

use App\Http\Request;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TagRepository;
use App\Services\Auth;
use App\Services\Curator;
use App\Services\FeedFetcher;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use PDO;
use PDOException;
use FeedIo\Factory as FeedIoFactory;
use FeedIo\FeedIo;

class App
{
    private Container $container;

    private Router $router;

    public function __construct()
    {
        $this->bootstrapEnvironment();

        $this->container = new Container();
        $this->registerBindings();

        $this->router = new Router($this->container);
        $this->container->instance(Router::class, $this->router);

        $this->registerRoutes();
    }

    public function handle(): void
    {
        /** @var Request $request */
        $request = $this->container->get(Request::class);

        $response = $this->router->dispatch($request->method(), $request->path());

        $response->send();
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function bootstrapEnvironment(): void
    {
        $dotenv = new Dotenv();
        $projectRoot = dirname(__DIR__, 2);

        $envFiles = [
            $projectRoot . '/.env',
            $projectRoot . '/.env.local',
        ];

        foreach ($envFiles as $file) {
            if (is_file($file)) {
                $dotenv->usePutenv()->load($file);
            }
        }
    }

    private function registerBindings(): void
    {
        $this->container->instance(Container::class, $this->container);

        $viewsPath = dirname(__DIR__) . '/Views';

        $this->container->singleton(Environment::class, static function () use ($viewsPath): Environment {
            $loader = new FilesystemLoader($viewsPath);

            return new Environment($loader);
        });

        $this->container->singleton(Request::class, static fn (): Request => Request::fromGlobals());

        $this->container->singleton(PDO::class, static function (): PDO {
            $config = require dirname(__DIR__, 2) . '/config/database.php';
            $driver = $config['driver'] ?? 'mysql';

            return match ($driver) {
                'mysql' => self::createMysqlPdo($config),
                default => throw new \RuntimeException(sprintf('Unsupported database driver "%s"', $driver)),
            };
        });

        $this->container->singleton(Auth::class, static fn (): Auth => new Auth());
        $this->container->singleton(FeedRepository::class, static fn (Container $container): FeedRepository => new FeedRepository($container->get(PDO::class)));
        $this->container->singleton(ItemRepository::class, static fn (Container $container): ItemRepository => new ItemRepository($container->get(PDO::class)));
        $this->container->singleton(CuratedLinkRepository::class, static fn (Container $container): CuratedLinkRepository => new CuratedLinkRepository($container->get(PDO::class)));
        $this->container->singleton(EditionRepository::class, static fn (Container $container): EditionRepository => new EditionRepository($container->get(PDO::class)));
        $this->container->singleton(TagRepository::class, static fn (Container $container): TagRepository => new TagRepository($container->get(PDO::class)));
        $this->container->singleton(Curator::class, static fn (Container $container): Curator => new Curator(
            $container->get(ItemRepository::class),
            $container->get(CuratedLinkRepository::class),
            $container->get(EditionRepository::class),
            $container->get(TagRepository::class)
        ));
        $this->container->singleton(FeedIo::class, static fn (): FeedIo => FeedIoFactory::create()->getFeedIo());
        $this->container->singleton(FeedFetcher::class, static fn (Container $container): FeedFetcher => new FeedFetcher(
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class),
            $container->get(FeedIo::class),
            $container->get(Logger::class)
        ));

        $logPath = dirname(__DIR__, 2) . '/storage/logs/app.log';
        $logDirectory = dirname($logPath);

        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0775, true);
        }

        $this->container->singleton(Logger::class, static function () use ($logPath): Logger {
            $logger = new Logger('app');
            $logger->pushHandler(new StreamHandler($logPath));

            return $logger;
        });
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function createMysqlPdo(array $config): PDO
    {
        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            throw new \RuntimeException('Database connection failed: PDO MySQL driver is not installed. Enable the pdo_mysql extension.');
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $charset = $config['charset'] ?? 'utf8mb4';
        $database = $config['database'] ?? 'thenewslog';
        $socket = $config['socket'] ?? null;

        $dsn = $socket
            ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s', $socket, $database, $charset)
            : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);

        $username = $config['user'] ?? 'root';
        $password = $config['password'] ?? '';

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new \RuntimeException('Database connection failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private function registerRoutes(): void
    {
        $this->router->get('/', 'App\Controllers\HomeController@__invoke');

        $this->router->match(['GET', 'POST'], '/admin', 'App\Controllers\Admin\AuthController@login');
        $this->router->match(['GET', 'POST'], '/admin/login', 'App\Controllers\Admin\AuthController@login');
        $this->router->get('/admin/inbox', 'App\Controllers\Admin\InboxController@index');
        $this->router->get('/admin/curate/{id}', 'App\Controllers\Admin\CurateController@show');
        $this->router->post('/admin/curate/{id}', 'App\Controllers\Admin\CurateController@store');
        $this->router->get('/admin/edition/{date}', 'App\Controllers\Admin\EditionController@show');
        $this->router->get('/admin/feeds', 'App\Controllers\Admin\FeedController@index');
        $this->router->get('/admin/logout', 'App\Controllers\Admin\AuthController@logout');
        $this->router->get('/stream', 'App\Controllers\StreamController@__invoke');
        $this->router->get('/tags', 'App\Controllers\TagController@index');
        $this->router->get('/tags/{slug}', 'App\Controllers\TagController@show');
        $this->router->setNotFoundHandler('App\Controllers\ErrorController@notFound');
    }
}
