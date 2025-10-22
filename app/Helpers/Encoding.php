<?php

namespace App\Helpers;

class Encoding
{
    public static function ensureUtf8(?string $value, string $replacement = ''): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]+/u', $replacement, $value);

        return $sanitized === null ? $replacement : $sanitized;
    }
}
