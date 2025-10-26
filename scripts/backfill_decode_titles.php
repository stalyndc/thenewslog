<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;
use App\Helpers\Encoding;
use PDO;

// Bootstrap the container/DB
$app = new App();
$container = $app->container();

/** @var PDO $pdo */
$pdo = $container->get(PDO::class);

/**
 * Normalize multiple string columns by decoding HTML entities and ensuring UTF-8.
 *
 * @param array<string,string[]> $columnsByTable Map: table => list of columns
 * @return array<string,int> Updated counts per table
 */
function processTables(PDO $pdo, array $columnsByTable): array
{
    $limit = 500;
    $results = [];

    foreach ($columnsByTable as $table => $columns) {
        $idCol = 'id';
        $updated = 0;
        $lastId = 0;

        $selectCols = implode(', ', array_map(static fn($c) => "$c AS `$c`", $columns));
        $select = $pdo->prepare("SELECT {$idCol} AS id, {$selectCols} FROM {$table} WHERE {$idCol} > :last_id ORDER BY {$idCol} ASC LIMIT :limit");

        while (true) {
            $select->bindValue(':last_id', $lastId, PDO::PARAM_INT);
            $select->bindValue(':limit', $limit, PDO::PARAM_INT);
            $select->execute();
            $rows = $select->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!$rows) { break; }

            foreach ($rows as $row) {
                $id = (int) $row['id'];
                $set = [];
                foreach ($columns as $col) {
                    $orig = (string) ($row[$col] ?? '');
                    if ($orig === '') { continue; }
                    $clean = Encoding::ensureUtf8($orig) ?? '';
                    $decoded = Encoding::decodeHtmlEntities($clean) ?? $clean;
                    if ($decoded !== '' && $decoded !== $orig) {
                        $set[$col] = $decoded;
                    }
                }

                if (!empty($set)) {
                    $assignments = implode(', ', array_map(static fn($c) => "$c = :$c", array_keys($set)));
                    $sql = "UPDATE {$table} SET {$assignments}, updated_at = CURRENT_TIMESTAMP WHERE {$idCol} = :id";
                    $stmt = $pdo->prepare($sql);
                    foreach ($set as $col => $val) {
                        $stmt->bindValue(":".$col, $val);
                    }
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $updated++;
                }
                $lastId = $id;
            }
        }

        $results[$table] = $updated;
    }

    return $results;
}

$results = processTables($pdo, [
    'items' => ['title', 'source_name', 'author'],
    'curated_links' => ['title', 'blurb', 'source_name', 'curator_notes', 'tags_csv'],
    'editions' => ['title', 'intro'],
]);

foreach ($results as $table => $count) {
    fwrite(STDOUT, sprintf("%s: updated %d rows\n", $table, $count));
}
