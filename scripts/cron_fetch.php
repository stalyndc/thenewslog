<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\FeedFetcher;

$fetcher = new FeedFetcher();
$fetcher->fetch();
