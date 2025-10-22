<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use Psr\Log\LoggerInterface;

$app = new App();
$container = $app->container();

/** @var EditionRepository $editions */
$editions = $container->get(EditionRepository::class);
/** @var CuratedLinkRepository $curatedLinks */
$curatedLinks = $container->get(CuratedLinkRepository::class);
/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

$due = $editions->dueForPublication();

if (empty($due)) {
    return;
}

foreach ($due as $edition) {
    $editionId = (int) $edition['id'];
    $scheduledFor = $edition['scheduled_for'] ?? date('Y-m-d H:i:s');

    try {
        $editions->updateStatus($editionId, 'published', null, $scheduledFor);
        $curatedLinks->publishAllForEdition($editionId, $scheduledFor);

        $logger->info('Published scheduled edition', [
            'edition_id' => $editionId,
            'edition_date' => $edition['edition_date'] ?? null,
            'scheduled_for' => $edition['scheduled_for'] ?? null,
        ]);
    } catch (\Throwable $exception) {
        $logger->error('Failed to publish scheduled edition', [
            'edition_id' => $editionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
