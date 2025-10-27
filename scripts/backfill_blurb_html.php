<?php

declare(strict_types=1);

// Backfill curated_links.blurb_html from existing blurb (plain text -> sanitized HTML)

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap\App;
use App\Services\HtmlSanitizer;
// Note: use of PDO in global namespace does not require `use PDO`.

$app = new App();
$container = $app->container();
/** @var PDO $pdo */
$pdo = $container->get(PDO::class);
/** @var HtmlSanitizer $sanitizer */
$sanitizer = $container->get(HtmlSanitizer::class);

$stmt = $pdo->query("SELECT id, blurb, blurb_html FROM curated_links WHERE (blurb_html IS NULL OR blurb_html = '') AND blurb IS NOT NULL AND blurb <> ''");
$rows = $stmt->fetchAll() ?: [];

$updated = 0;
foreach ($rows as $row) {
    $id = (int) $row['id'];
    $blurb = (string) $row['blurb'];
    $html = nl2br(htmlspecialchars(trim($blurb), ENT_QUOTES, 'UTF-8'));
    $clean = $sanitizer->clean($html);
    $u = $pdo->prepare('UPDATE curated_links SET blurb_html = :html, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $u->execute(['html' => $clean, 'id' => $id]);
    $updated++;
}

fwrite(STDOUT, "Updated {$updated} rows\n");
