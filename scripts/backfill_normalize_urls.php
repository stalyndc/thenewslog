<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;
use App\Helpers\Url;
use PDO;

$app = new App();
$container = $app->container();

/** @var PDO $pdo */
$pdo = $container->get(PDO::class);

$select = $pdo->prepare("SELECT id, url, url_hash, status FROM items WHERE url LIKE :pattern ORDER BY id ASC");
$select->execute([':pattern' => '%#%']);
$rows = $select->fetchAll(PDO::FETCH_ASSOC) ?: [];

$check = $pdo->prepare('SELECT id FROM items WHERE url_hash = :hash AND id <> :id LIMIT 1');
$update = $pdo->prepare('UPDATE items SET url = :url, url_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id');

$updated = 0;
$skipped = 0;

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $oldUrl = (string) $row['url'];
    $normalized = Url::normalize($oldUrl);
    if ($normalized === $oldUrl) {
        continue; // nothing to do
    }

    $newHash = sha1($normalized);
    $check->execute([':hash' => $newHash, ':id' => $id]);
    $dupe = $check->fetch(PDO::FETCH_ASSOC);

    if ($dupe) {
        // Skip conflicting update; report for manual review
        $skipped++;
        fwrite(STDERR, sprintf("skip id=%d -> conflicts with id=%d for %s\n", $id, (int) $dupe['id'], $normalized));
        continue;
    }

    $update->execute([':url' => $normalized, ':hash' => $newHash, ':id' => $id]);
    $updated++;
}

fwrite(STDOUT, sprintf("URLs updated: %d, skipped (conflicts): %d\n", $updated, $skipped));

