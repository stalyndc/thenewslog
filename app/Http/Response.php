<?php

namespace App\Http;

class Response
{
    private string $content;

    /**
     * @var array<string, string>
     */
    private array $headers;

    private int $status;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value), true);
        }

        echo $this->content;
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $content = json_encode($data, JSON_THROW_ON_ERROR);
        $response = new self($content ?: '', $status);
        $response->setHeader('Content-Type', 'application/json');

        return $response;
    }

    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self('', $status);
        $response->setHeader('Location', $url);

        return $response;
    }
}
