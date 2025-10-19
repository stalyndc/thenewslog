<?php

namespace App\Services;

class Auth
{
    public const SESSION_KEY = 'admin_authenticated';

    public function attempt(?string $email, ?string $password): bool
    {
        if (!$this->hasValidCredentials($email, $password)) {
            return false;
        }

        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION['admin_email'] = getenv('ADMIN_EMAIL');
        $_SESSION['admin_authenticated_at'] = time();

        return true;
    }

    public function check(): bool
    {
        return !empty($_SESSION[self::SESSION_KEY]);
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION['admin_email'], $_SESSION['admin_authenticated_at']);
    }

    private function hasValidCredentials(?string $email, ?string $password): bool
    {
        $configuredEmail = getenv('ADMIN_EMAIL') ?: '';
        $configuredHash = getenv('ADMIN_PASS_HASH') ?: '';

        if ($configuredEmail === '' || $configuredHash === '') {
            return false;
        }

        if ($email === null || $password === null) {
            return false;
        }

        if (strcasecmp(trim($email), $configuredEmail) !== 0) {
            return false;
        }

        return password_verify($password, $configuredHash);
    }
}
