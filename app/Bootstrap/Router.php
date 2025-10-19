<?php

namespace App\Bootstrap;

use App\Http\Response;

class Router
{
    private Container $container;

    /**
     * @var array<string, array<int, array{pattern: string, parameters: array<int, string>, handler: mixed}>>
     */
    private array $routes = [];

    private mixed $notFoundHandler = null;

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

    public function setNotFoundHandler(mixed $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    public function dispatch(string $method, string $path): Response
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

                return $this->invoke($route['handler'], $arguments);
            }
        }

        if ($this->notFoundHandler !== null) {
            return $this->invoke($this->notFoundHandler, []);
        }

        return new Response('404 Not Found', 404);
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

    private function invoke(mixed $handler, array $arguments): Response
    {
        $result = match (true) {
            is_string($handler) && str_contains($handler, '@') => $this->invokeClassString($handler, $arguments),
            is_array($handler) => $this->invokeClassArray($handler, $arguments),
            is_callable($handler) => $this->container->call($handler, $arguments),
            default => throw new \InvalidArgumentException('Invalid route handler provided.'),
        };

        return $this->normalizeResponse($result);
    }

    private function invokeClassString(string $handler, array $arguments): mixed
    {
        [$class, $method] = explode('@', $handler, 2);
        $instance = $this->container->get($class);

        return $this->container->call([$instance, $method], $arguments);
    }

    private function invokeClassArray(array $handler, array $arguments): mixed
    {
        [$classOrInstance, $method] = $handler;
        $instance = is_string($classOrInstance)
            ? $this->container->get($classOrInstance)
            : $classOrInstance;

        return $this->container->call([$instance, $method], $arguments);
    }

    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return new Response($result);
        }

        if ($result === null) {
            return new Response();
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return new Response((string) $result);
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
