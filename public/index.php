<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;

try {
    $app = new App();
    $app->handle();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Application bootstrap failed: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
}
