<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * HTML utility functions for template rendering and escaping.
 */
class Html
{
    /**
     * Escape HTML special characters safely.
     *
     * @param mixed $value Value to escape
     * @param string $context Context for escaping (html, js, attr, css, url)
     */
    public static function escape(mixed $value, string $context = 'html'): string
    {
        $string = (string) $value;

        return match ($context) {
            'js' => self::escapeJs($string),
            'attr' => htmlspecialchars($string, ENT_QUOTES, 'UTF-8'),
            'css' => self::escapeCss($string),
            'url' => rawurlencode($string),
            default => htmlspecialchars($string, ENT_QUOTES, 'UTF-8'),
        };
    }

    /**
     * Truncate text to a maximum length.
     *
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to append if truncated
     */
    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        $truncated = substr($text, 0, $length);

        return rtrim($truncated) . $suffix;
    }

    /**
     * Convert plain text to safe HTML with line breaks.
     *
     * @param string $text Plain text
     */
    public static function textToHtml(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        return str_replace("\n", '<br />', $escaped);
    }

    /**
     * Generate a URL-safe slug from text.
     *
     * @param string $text Text to slugify
     */
    public static function slug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: '';
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Build HTML attributes string from array.
     *
     * @param array<string, mixed> $attributes Attributes
     */
    public static function attributes(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $parts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                continue;
            }

            $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            $parts[] = sprintf('%s="%s"', htmlspecialchars($key, ENT_QUOTES, 'UTF-8'), $escapedValue);
        }

        return implode(' ', $parts);
    }

    private static function escapeJs(string $value): string
    {
        $safe = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return str_replace(['\\', "\n", "\r"], ['\\\\', '\\n', '\\r'], $safe);
    }

    private static function escapeCss(string $value): string
    {
        return preg_replace_callback('/[^a-z0-9]/i', static function (array $matches): string {
            $char = $matches[0];
            $code = ord($char);

            return sprintf('\\%x ', $code);
        }, $value) ?? $value;
    }
}
