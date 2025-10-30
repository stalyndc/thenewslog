<?php

declare(strict_types=1);

// Harden session behavior before start
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

// Lightweight health endpoint short-circuit to avoid full bootstrap
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
if (preg_match('#^/healthz/?$#', $path) === 1) {
    header('Content-Type', 'application/json');
    header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode(['status' => 'ok', 'time' => gmdate('c')]);
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\App;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_samesite' => 'Lax',
    ]);
}

try {
    $app = new App();
    $app->handle();
} catch (Throwable $exception) {
    // Never expose exception details to the end-user during bootstrap failures
    http_response_code(500);
    error_log('[bootstrap] ' . $exception->getMessage() . "\n" . $exception->getTraceAsString());

    // In development, show the error. In production, show generic message.
    $isDev = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || str_contains($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1');
    if ($isDev) {
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    } else {
        echo 'An unexpected error occurred. Please try again later.';
    }
}
