<?php

namespace App\Http;

class Request
{
    private string $method;

    private string $path;

    /**
     * @var array<string, mixed>
     */
    private array $query;

    /**
     * @var array<string, mixed>
     */
    private array $post;

    /**
     * @var array<string, mixed>
     */
    private array $server;

    private function __construct(
        string $method,
        string $path,
        array $query,
        array $post,
        array $server
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return new self(
            $method,
            $path,
            $_GET,
            $_POST,
            $_SERVER
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        return $this->query[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $this->server[$normalized] ?? $default;
    }
}
