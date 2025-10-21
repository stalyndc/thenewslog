<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Request;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        $token = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    public function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $stored = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    public function assertValid(?string $token): void
    {
        if (!$this->validate($token)) {
            throw new \RuntimeException('Invalid CSRF token.');
        }
    }

    public function extractToken(Request $request): ?string
    {
        $input = $request->input('_token');

        if (is_string($input) && $input !== '') {
            return $input;
        }

        $headerNames = ['HX-CSRF-TOKEN', 'X-CSRF-TOKEN'];

        foreach ($headerNames as $name) {
            $header = $request->header($name);

            if (is_string($header) && $header !== '') {
                return $header;
            }
        }

        return null;
    }
}
