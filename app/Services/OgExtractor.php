<?php

declare(strict_types=1);

namespace App\Services;

class OgExtractor
{
    /**
     * Extract Open Graph metadata from a URL.
     *
     * Currently a stub implementation. Future enhancement to fetch and parse
     * Open Graph tags (og:title, og:description, og:image) from a URL.
     *
     * @param string $url The URL to extract metadata from
     * @return array<string, string> Array with keys: title, description, image
     */
    public function extract(string $url): array
    {
        return [
            'title' => '',
            'description' => '',
            'image' => '',
        ];
    }
}
