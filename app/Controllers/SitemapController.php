<?php

namespace App\Controllers;

use App\Http\Response;

class SitemapController
{
    public function __invoke(): Response
    {
        $path = dirname(__DIR__, 2) . '/public/sitemap.xml';

        if (!is_file($path)) {
            return new Response('Sitemap not generated', 404);
        }

        $contents = file_get_contents($path) ?: '';

        $response = new Response($contents);
        $response->setHeader('Content-Type', 'application/xml; charset=utf-8');

        return $response;
    }
}
