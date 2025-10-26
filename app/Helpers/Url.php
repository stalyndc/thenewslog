<?php

namespace App\Helpers;

class Url
{
    public static function normalize(string $url): string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return '';
        }

        if (!str_contains($trimmed, '://')) {
            $trimmed = 'https://' . ltrim($trimmed, '/');
        }

        $parts = parse_url($trimmed);

        if ($parts === false || empty($parts['host'])) {
            return $trimmed;
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? null;
        $path = $parts['path'] ?? '/';
        $cleanPath = preg_replace('#/+#', '/', $path);
        if ($cleanPath === null || $cleanPath === '') {
            $cleanPath = '/';
        }
        $path = $cleanPath;

        $query = '';
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            ksort($queryParams);
            $query = http_build_query($queryParams);
        }

        $normalized = $scheme . '://' . $host;

        if ($port !== null && !($scheme === 'http' && (int) $port === 80) && !($scheme === 'https' && (int) $port === 443)) {
            $normalized .= ':' . $port;
        }

        $normalized .= $path;

        if ($query !== '') {
            $normalized .= '?' . $query;
        }

        // Intentionally drop URL fragments (they do not change the resource)
        // to improve duplicate detection and canonicalization.

        return $normalized;
    }

    public static function isValid(string $url): bool
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return false;
        }

        $originalParts = parse_url($trimmed);

        if ($originalParts !== false && isset($originalParts['scheme'])) {
            $scheme = strtolower((string) $originalParts['scheme']);

            if (!in_array($scheme, ['http', 'https'], true)) {
                return false;
            }
        }

        $normalized = self::normalize($trimmed);
        $parts = parse_url($normalized);

        if ($parts === false) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if ($scheme === '' || $host === '') {
            return false;
        }

        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return filter_var($normalized, FILTER_VALIDATE_URL) !== false;
    }
}
