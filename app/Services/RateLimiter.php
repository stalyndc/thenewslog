<?php

declare(strict_types=1);

namespace App\Services;

class RateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900;

    private string $storePath;

    public function __construct()
    {
        $this->storePath = dirname(__DIR__, 2) . '/storage/rate_limit';

        if (!is_dir($this->storePath)) {
            mkdir($this->storePath, 0775, true);
        }
    }

    public function isBlocked(string $identifier): bool
    {
        $data = $this->getAttemptData($identifier);

        if ($data === null) {
            return false;
        }

        $timeRemaining = (int) $data['blocked_until'] - time();

        return $timeRemaining > 0;
    }

    public function recordFailure(string $identifier): void
    {
        $data = $this->getAttemptData($identifier) ?? [
            'attempts' => 0,
            'first_attempt_at' => time(),
            'blocked_until' => 0,
        ];

        $data['attempts']++;
        $data['last_attempt_at'] = time();

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            $data['blocked_until'] = time() + self::LOCKOUT_DURATION;
        }

        $this->saveAttemptData($identifier, $data);
    }

    public function recordSuccess(string $identifier): void
    {
        $this->clearAttemptData($identifier);
    }

    public function getTimeRemaining(string $identifier): int
    {
        $data = $this->getAttemptData($identifier);

        if ($data === null) {
            return 0;
        }

        $timeRemaining = (int) $data['blocked_until'] - time();

        return max(0, $timeRemaining);
    }

    /**
     * Read attempt data from disk. Supports both JSON (current) and PHP serialize (legacy).
     *
     * @return array{attempts: int, first_attempt_at: int, blocked_until: int, last_attempt_at: int}|null
     */
    private function getAttemptData(string $identifier): ?array
    {
        $file = $this->getFilePath($identifier);

        if (!is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);

        if ($content === false) {
            return null;
        }

        // Prefer JSON format
        $data = null;
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        } catch (\Throwable) {
            // not JSON; try legacy serialized array
            $legacy = @unserialize($content, ['allowed_classes' => false]);
            if (is_array($legacy)) {
                $data = $legacy;
            }
        }

        if (!is_array($data)) {
            return null;
        }

        // Normalize types
        $attempts = (int) ($data['attempts'] ?? 0);
        $first = (int) ($data['first_attempt_at'] ?? time());
        $blocked = (int) ($data['blocked_until'] ?? 0);
        $last = (int) ($data['last_attempt_at'] ?? $first);

        return [
            'attempts' => $attempts,
            'first_attempt_at' => $first,
            'blocked_until' => $blocked,
            'last_attempt_at' => $last,
        ];
    }

    /**
     * Persist attempt data as JSON.
     *
     * @param array<string, mixed> $data
     */
    private function saveAttemptData(string $identifier, array $data): void
    {
        $file = $this->getFilePath($identifier);
        $payload = [
            'attempts' => (int) ($data['attempts'] ?? 0),
            'first_attempt_at' => (int) ($data['first_attempt_at'] ?? time()),
            'blocked_until' => (int) ($data['blocked_until'] ?? 0),
            'last_attempt_at' => (int) ($data['last_attempt_at'] ?? time()),
        ];

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            // Fallback to a simple encode without exceptions if the extension differs
            $json = json_encode($payload);
        }

        file_put_contents($file, $json === false ? '{}' : $json, LOCK_EX);
    }

    private function clearAttemptData(string $identifier): void
    {
        $file = $this->getFilePath($identifier);

        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function getFilePath(string $identifier): string
    {
        $hash = hash('sha256', $identifier);

        return $this->storePath . '/' . $hash . '.rate';
    }
}
