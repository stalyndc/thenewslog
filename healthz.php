<?php
// Health check: shallow by default; deep when ?deep=1
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (isset($_GET['deep']) && $_GET['deep'] === '1') {
    try {
        require __DIR__ . '/vendor/autoload.php';
        $app = new \App\Bootstrap\App();
        $container = $app->container();
        /** @var PDO $pdo */
        $pdo = $container->get(PDO::class);
        $pdo->query('SELECT 1');
        echo json_encode([
            'status' => 'ok',
            'time' => gmdate('c'),
            'db' => 'ok',
        ]);
        exit;
    } catch (Throwable $e) {
        http_response_code(503);
        echo json_encode([
            'status' => 'error',
            'time' => gmdate('c'),
            'db' => 'error',
        ]);
        exit;
    }
}

echo json_encode(['status' => 'ok', 'time' => gmdate('c')]);
exit;
