<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;
use App\Repositories\FeedRepository;

$app = new App();
$container = $app->container();

/** @var FeedRepository $feeds */
$feeds = $container->get(FeedRepository::class);

$configPath = dirname(__DIR__) . '/config/feeds.seed.php';
$seedFeeds = file_exists($configPath) ? require $configPath : [];

foreach ($seedFeeds as $feed) {
    if (!is_array($feed) || empty($feed['feed_url'])) {
        continue;
    }

    $record = $feeds->ensure($feed);

    echo sprintf("Seeded feed: %s (%s)\n", $record['title'] ?? 'Unknown', $record['feed_url'] ?? '');
}
