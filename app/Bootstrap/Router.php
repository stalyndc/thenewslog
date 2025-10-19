<?php

namespace App\Bootstrap;

class Router
{
    private Container $container;

    /**
     * @var array<string, array<int, array{pattern: string, parameters: array<int, string>, handler: mixed}>>
     */
    private array $routes = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $path, mixed $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function match(array $methods, string $path, mixed $handler): void
    {
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $handler);
        }
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) === 1) {
                $arguments = [];

                foreach ($route['parameters'] as $name) {
                    if (isset($matches[$name])) {
                        $arguments[$name] = $matches[$name];
                    }
                }

                $this->invoke($route['handler'], $arguments);

                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    private function addRoute(string $method, string $path, mixed $handler): void
    {
        $method = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);
        [$pattern, $parameters] = $this->compilePath($normalizedPath);

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'parameters' => $parameters,
            'handler' => $handler,
        ];
    }

    private function invoke(mixed $handler, array $arguments): void
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $instance = $this->container->get($class);
            $this->container->call([$instance, $method], $arguments);

            return;
        }

        if (is_array($handler)) {
            [$classOrInstance, $method] = $handler;
            $instance = is_string($classOrInstance)
                ? $this->container->get($classOrInstance)
                : $classOrInstance;

            $this->container->call([$instance, $method], $arguments);

            return;
        }

        if (is_callable($handler)) {
            $this->container->call($handler, $arguments);

            return;
        }

        throw new \InvalidArgumentException('Invalid route handler provided.');
    }

    private function compilePath(string $path): array
    {
        $parameterNames = [];
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function (array $matches) use (&$parameterNames): string {
                $parameterNames[] = $matches[1];

                return sprintf('(?P<%s>[^/]+)', $matches[1]);
            },
            $path
        );

        $pattern = sprintf('#^%s$#', $pattern ?? '');

        return [$pattern, $parameterNames];
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        $path = '/' . ltrim($path, '/');

        return rtrim($path, '/') ?: '/';
    }
}
