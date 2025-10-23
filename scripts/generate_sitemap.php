<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use App\Repositories\TagRepository;

$app = new App();
$container = $app->container();

/** @var EditionRepository $editions */
$editions = $container->get(EditionRepository::class);
/** @var CuratedLinkRepository $links */
$links = $container->get(CuratedLinkRepository::class);
/** @var TagRepository $tags */
$tagsRepo = $container->get(TagRepository::class);

$baseUrl = rtrim(getenv('BASE_URL') ?: 'http://localhost:8000', '/');
$urls = [];

$append = static function (string $loc, ?string $lastMod = null) use (&$urls, $baseUrl): void {
    $urls[] = [
        'loc' => $baseUrl . $loc,
        'lastmod' => $lastMod ? gmdate('Y-m-d\TH:i:sP', strtotime($lastMod)) : null,
    ];
};

$append('/', null);
$append('/editions', null);
$append('/tags', null);
$append('/rss/daily.xml', null);

foreach ($editions->publishedWithCounts(1, 500) as $edition) {
    $append('/editions/' . $edition['edition_date'], $edition['published_at'] ?? $edition['updated_at'] ?? null);
}

foreach ($tagsRepo->allWithCounts() as $tag) {
    if ((int) $tag['link_count'] > 0) {
        $append('/tags/' . $tag['slug'], null);
    }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($urls as $url) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";
    if ($url['lastmod']) {
        echo "    <lastmod>{$url['lastmod']}</lastmod>\n";
    }
    echo "  </url>\n";
}

echo "</urlset>\n";
