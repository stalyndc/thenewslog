<?php

namespace App\Helpers;

class Encoding
{
    public static function decodeHtmlEntities(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Decode numeric and named HTML entities to their UTF-8 characters
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function ensureUtf8(?string $value, string $replacement = ''): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (self::isUtf8($value)) {
            return $value;
        }

        $converted = self::mbConvert($value);

        if ($converted !== false && self::isUtf8($converted)) {
            return $converted;
        }

        $converted = self::iconvConvert($value);

        if ($converted !== false && self::isUtf8($converted)) {
            return $converted;
        }

        $sanitized = @preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/', $replacement, $value);

        if (!is_string($sanitized) || $sanitized === '') {
            $sanitized = self::stripNonAscii($value, $replacement);
        }

        return $sanitized === null ? $replacement : $sanitized;
    }

    private static function isUtf8(string $value): bool
    {
        if (function_exists('mb_check_encoding')) {
            return mb_check_encoding($value, 'UTF-8');
        }

        return @preg_match('//u', $value) === 1;
    }

    private static function mbConvert(string $value): string|false
    {
        if (!function_exists('mb_convert_encoding')) {
            return false;
        }

        return @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private static function iconvConvert(string $value): string|false
    {
        if (!function_exists('iconv')) {
            return false;
        }

        return @iconv('UTF-8', 'UTF-8//IGNORE', $value);
    }

    private static function stripNonAscii(string $value, string $replacement): string
    {
        $clean = '';

        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];
            $ord = ord($char);

            if ($ord >= 32 && $ord <= 126) {
                $clean .= $char;
                continue;
            }

            // Preserve common whitespace characters
            if ($ord === 9 || $ord === 10 || $ord === 13) {
                $clean .= $char;
                continue;
            }

            if ($replacement !== '') {
                $clean .= $replacement;
            }
        }

        return $clean;
    }
}
