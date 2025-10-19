<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;
use App\Services\FeedFetcher;

$app = new App();
$container = $app->container();

/** @var FeedFetcher $fetcher */
$fetcher = $container->get(FeedFetcher::class);

$fetcher->fetch();
