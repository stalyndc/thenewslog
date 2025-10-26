<?php

declare(strict_types=1);

// Adds helpful indexes conditionally by checking information_schema first.
// Use this if your MySQL version does not support `CREATE INDEX IF NOT EXISTS`.
//
// Usage:
//   php scripts/add_indexes.php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;
use PDO;

$app = new App();
$container = $app->container();

/** @var PDO $pdo */
$pdo = $container->get(PDO::class);

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $sql = 'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :idx LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['table' => $table, 'idx' => $index]);
    return (bool) $stmt->fetchColumn();
}

function ensureIndex(PDO $pdo, string $table, string $index, string $definition): void
{
    if (indexExists($pdo, $table, $index)) {
        echo "[index] Exists: {$table}.{$index}\n";
        return;
    }

    $sql = "CREATE INDEX `{$index}` ON `{$table}` ({$definition})";
    $pdo->exec($sql);
    echo "[index] Created: {$table}.{$index}\n";
}

try {
    // curated_links.published_at
    ensureIndex($pdo, 'curated_links', 'idx_curated_links_published_at', 'published_at');

    // items.created_at (helps inbox ordering)
    ensureIndex($pdo, 'items', 'idx_items_created_at', 'created_at');

    echo "All indexes ensured.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[index] Error: " . $e->getMessage() . "\n");
    exit(1);
}

