<?php

namespace App\Bootstrap;

use App\Http\Request;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TagRepository;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Curator;
use App\Services\Feed\ConditionalClient;
use App\Services\FeedFetcher;
use App\Services\RateLimiter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;
use Twig\TwigFunction;
use PDO;
use PDOException;
use FeedIo\FeedIo;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;

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

        $productionEnv = $projectRoot . '/.env.production';
        $localEnv = $projectRoot . '/.env.local';
        $baseEnv = $projectRoot . '/.env';

        if (is_file($productionEnv)) {
            $dotenv->usePutenv()->load($productionEnv);
        } elseif (is_file($baseEnv)) {
            $dotenv->usePutenv()->load($baseEnv);
        }

        if (is_file($localEnv)) {
            $dotenv->usePutenv()->load($localEnv);
        }

        $timezone = getenv('APP_TIMEZONE') ?: 'America/New_York';

        if (@date_default_timezone_set($timezone) === false) {
            date_default_timezone_set('America/New_York');
        }
    }

    private function registerBindings(): void
    {
        $this->container->instance(Container::class, $this->container);

        $viewsPath = dirname(__DIR__) . '/Views';
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

        $this->container->singleton(LoggerInterface::class, static function (Container $container): LoggerInterface {
            return $container->get(Logger::class);
        });

        $this->container->singleton(Csrf::class, static fn (): Csrf => new Csrf());

        $this->container->singleton(Environment::class, static function (Container $container) use ($viewsPath): Environment {
            $loader = new FilesystemLoader($viewsPath);

            $environment = new Environment($loader, [
                'autoescape' => 'html',
            ]);

            $environment->addGlobal('csrf_token', $container->get(Csrf::class)->token());
            $environment->addFunction(new TwigFunction('csrf_field', static function () use ($container): Markup {
                $token = htmlspecialchars($container->get(Csrf::class)->token(), ENT_QUOTES, 'UTF-8');

                return new Markup('<input type="hidden" name="_token" value="' . $token . '">', 'UTF-8');
            }));

            return $environment;
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
        $this->container->singleton(RateLimiter::class, static fn (): RateLimiter => new RateLimiter());
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
        $this->container->singleton(ConditionalClient::class, static fn (): ConditionalClient => new ConditionalClient(new GuzzleClient()));
        $this->container->singleton(FeedIo::class, static fn (Container $container): FeedIo => new FeedIo(
            $container->get(ConditionalClient::class),
            $container->get(LoggerInterface::class)
        ));
        $this->container->singleton(FeedFetcher::class, static fn (Container $container): FeedFetcher => new FeedFetcher(
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class),
            $container->get(FeedIo::class),
            $container->get(LoggerInterface::class),
            $container->get(ConditionalClient::class)
        ));
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
        $this->router->get('/admin/inbox/partial', 'App\Controllers\Admin\InboxController@partial');
        $this->router->get('/admin/curate/{id}', 'App\Controllers\Admin\CurateController@show');
        $this->router->post('/admin/curate/{id}', 'App\Controllers\Admin\CurateController@store');
        $this->router->post('/admin/curate/{id}/delete', 'App\Controllers\Admin\CurateController@destroy');
        $this->router->match(['GET', 'POST'], '/admin/edition/{date}', 'App\Controllers\Admin\EditionController@show');
        $this->router->get('/admin/feeds', 'App\Controllers\Admin\FeedController@index');
        $this->router->post('/admin/feeds/refresh', 'App\Controllers\Admin\FeedController@refresh');
        $this->router->post('/admin/feeds', 'App\Controllers\Admin\FeedController@store');
        $this->router->post('/admin/feeds/{id}', 'App\Controllers\Admin\FeedController@update');
        $this->router->post('/admin/feeds/{id}/delete', 'App\Controllers\Admin\FeedController@destroy');
        $this->router->post('/admin/inbox/delete', 'App\Controllers\Admin\InboxController@delete');
        $this->router->post('/admin/inbox/ignore', 'App\Controllers\Admin\InboxController@ignore');
        $this->router->post('/admin/logout', 'App\Controllers\Admin\AuthController@logout');
        $this->router->get('/stream', 'App\Controllers\StreamController@__invoke');
        $this->router->get('/tags', 'App\Controllers\TagController@index');
        $this->router->get('/tags/{slug}', 'App\Controllers\TagController@show');
        $this->router->get('/editions', 'App\Controllers\EditionArchiveController@index');
        $this->router->get('/editions/{date}', 'App\Controllers\EditionArchiveController@show');
        $this->router->get('/rss/daily.xml', 'App\Controllers\RssController@daily');
        $this->router->get('/rss/stream.xml', 'App\Controllers\RssController@stream');
        $this->router->get('/sitemap.xml', 'App\Controllers\SitemapController@__invoke');
        $this->router->setNotFoundHandler('App\Controllers\ErrorController@notFound');
    }
}
