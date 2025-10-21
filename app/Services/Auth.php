<?php

namespace App\Services;

class Auth
{
    public const SESSION_KEY = 'admin_authenticated';

    private const SESSION_TIMEOUT = 3600;

    public function attempt(?string $email, ?string $password): bool
    {
        if (!$this->hasValidCredentials($email, $password)) {
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION['admin_email'] = getenv('ADMIN_EMAIL');
        $_SESSION['admin_authenticated_at'] = time();
        $_SESSION['admin_last_activity'] = time();

        return true;
    }

    public function check(): bool
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        $lastActivity = $_SESSION['admin_last_activity'] ?? null;
        $timeout = $this->sessionTimeout();

        if (!is_int($lastActivity)) {
            $lastActivity = is_numeric($lastActivity) ? (int) $lastActivity : null;
        }

        if ($lastActivity !== null && ($lastActivity <= 0 || (time() - $lastActivity) > $timeout)) {
            $this->logout();

            return false;
        }

        $_SESSION['admin_last_activity'] = time();

        return true;
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION['admin_email'], $_SESSION['admin_authenticated_at'], $_SESSION['admin_last_activity']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
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

    private function sessionTimeout(): int
    {
        $configured = getenv('ADMIN_SESSION_TIMEOUT');

        if ($configured !== false && $configured !== null && $configured !== '') {
            $value = (int) $configured;

            if ($value >= 300) {
                return $value;
            }
        }

        return self::SESSION_TIMEOUT;
    }
}
