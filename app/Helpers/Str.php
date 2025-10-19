<?php

namespace App\Helpers;

use voku\helper\ASCII;

class Str
{
    public static function slug(string $value): string
    {
        $ascii = ASCII::to_ascii($value);
        $ascii = strtolower($ascii);
        $ascii = preg_replace('/[^a-z0-9]+/i', '-', $ascii) ?: '';
        $ascii = trim($ascii, '-');

        return $ascii !== '' ? $ascii : 'edition-' . uniqid();
    }
}
