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
    private array $json;

    /**
     * @var array<string, mixed>
     */
    private array $server;

    private function __construct(
        string $method,
        string $path,
        array $query,
        array $post,
        array $json,
        array $server
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->post = $post;
        $this->json = $json;
        $this->server = $server;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $json = self::parseJsonBody();

        return new self(
            $method,
            $path,
            $_GET,
            $_POST,
            $json,
            $_SERVER
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function parseJsonBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') === false) {
            return [];
        }

        $body = file_get_contents('php://input');

        if ($body === false || $body === '') {
            return [];
        }

        $parsed = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return is_array($parsed) ? $parsed : [];
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

        if (array_key_exists($key, $this->json)) {
            return $this->json[$key];
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function json(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->json;
        }

        return $this->json[$key] ?? $default;
    }

    public function inputInt(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return is_int($value) || is_string($value) ? (int) $value : $default;
    }

    public function inputBool(string $key, bool $default = false): bool
    {
        $value = $this->input($key);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value || $default;
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
