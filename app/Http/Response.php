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

    private static ?string $cspNonce = null;

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
        $this->applySecurityHeaders();
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value), true);
        }

        echo $this->content;
    }

    private function applySecurityHeaders(): void
    {
        if (!isset($this->headers['X-Content-Type-Options'])) {
            $this->headers['X-Content-Type-Options'] = 'nosniff';
        }

        if (!isset($this->headers['X-Frame-Options'])) {
            $this->headers['X-Frame-Options'] = 'DENY';
        }

        if (!isset($this->headers['X-XSS-Protection'])) {
            $this->headers['X-XSS-Protection'] = '1; mode=block';
        }

        if (!isset($this->headers['Referrer-Policy'])) {
            $this->headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        }

        // Modern security headers (set defaults only if not already provided)
        if (!isset($this->headers['Content-Security-Policy'])) {
            $nonce = self::cspNonce();
            $this->headers['Content-Security-Policy'] = implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "img-src 'self' https: data:",
                "style-src 'self' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com data:",
                // Temporarily allow unpkg as a CDN fallback until vendor files are deployed everywhere
                sprintf("script-src 'self' 'nonce-%s' https://www.googletagmanager.com https://unpkg.com", $nonce),
                "connect-src 'self' https://www.googletagmanager.com",
            ]);
        }

        if (!isset($this->headers['Permissions-Policy'])) {
            $this->headers['Permissions-Policy'] = 'geolocation=(), camera=(), microphone=(), payment=(), browsing-topics=()';
        }

        if (!isset($this->headers['Cross-Origin-Opener-Policy'])) {
            $this->headers['Cross-Origin-Opener-Policy'] = 'same-origin';
        }

        if (!isset($this->headers['Cross-Origin-Resource-Policy'])) {
            $this->headers['Cross-Origin-Resource-Policy'] = 'same-origin';
        }

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            if (!isset($this->headers['Strict-Transport-Security'])) {
                $this->headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
            }
        }
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

    /**
     * Create a response with cache control headers.
     *
     * @param string $content Response body
     * @param int $maxAge Cache duration in seconds (0 = no-store)
     * @param bool $isPublic Whether cache is public or private
     */
    public static function cached(string $content, int $maxAge = 3600, bool $isPublic = false): self
    {
        $response = new self($content);
        $response->setHeader('Content-Type', 'text/html; charset=utf-8');

        if ($maxAge === 0) {
            $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } else {
            $visibility = $isPublic ? 'public' : 'private';
            $response->setHeader('Cache-Control', sprintf('%s, max-age=%d', $visibility, $maxAge));
        }

        return $response;
    }

    /**
     * Set Content Security Policy header.
     *
     * @param array<string, string|string[]> $directives CSP directives
     */
    public function setCsp(array $directives): void
    {
        $parts = [];

        foreach ($directives as $directive => $sources) {
            $sourcesStr = is_array($sources) ? implode(' ', $sources) : $sources;
            $parts[] = sprintf('%s %s', $directive, $sourcesStr);
        }

        $this->setHeader('Content-Security-Policy', implode('; ', $parts));
    }

    /**
     * Set X-Content-Security-Policy for older browsers (report-only).
     *
     * @param array<string, string|string[]> $directives CSP directives
     */
    public function setCspReportOnly(array $directives): void
    {
        $parts = [];

        foreach ($directives as $directive => $sources) {
            $sourcesStr = is_array($sources) ? implode(' ', $sources) : $sources;
            $parts[] = sprintf('%s %s', $directive, $sourcesStr);
        }

        $this->setHeader('Content-Security-Policy-Report-Only', implode('; ', $parts));
    }

    public static function cspNonce(): string
    {
        if (self::$cspNonce === null) {
            self::$cspNonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        }

        return self::$cspNonce;
    }
}
