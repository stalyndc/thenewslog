<?php

namespace App\Bootstrap;

use Symfony\Component\Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $this->router->dispatch($method, $path);
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
    }

    private function registerRoutes(): void
    {
        $this->router->get('/', 'App\\Controllers\\HomeController@__invoke');

        $this->router->match(['GET', 'POST'], '/admin/login', 'App\\Controllers\\Admin\\AuthController@login');
        $this->router->get('/admin/inbox', 'App\\Controllers\\Admin\\InboxController@index');
        $this->router->get('/admin/curate/{id}', 'App\\Controllers\\Admin\\CurateController@show');
        $this->router->post('/admin/curate/{id}', 'App\\Controllers\\Admin\\CurateController@store');
        $this->router->get('/admin/edition/{date}', 'App\\Controllers\\Admin\\EditionController@show');
        $this->router->get('/admin/feeds', 'App\\Controllers\\Admin\\FeedController@index');
    }
}
