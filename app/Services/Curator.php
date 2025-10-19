<?php

namespace App\Services;

use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use App\Repositories\ItemRepository;

class Curator
{
    private ItemRepository $items;

    private CuratedLinkRepository $curatedLinks;

    private EditionRepository $editions;

    public function __construct(
        ItemRepository $items,
        CuratedLinkRepository $curatedLinks,
        EditionRepository $editions
    ) {
        $this->items = $items;
        $this->curatedLinks = $curatedLinks;
        $this->editions = $editions;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array{item: array<string, mixed>|null, curated: array<string, mixed>|null, edition: array<string, mixed>|null}
     */
    public function curate(int $itemId, array $input = []): array
    {
        $item = $this->items->find($itemId);

        if ($item === null) {
            throw new \RuntimeException('Item not found.');
        }

        $title = trim((string) ($input['title'] ?? ($item['title'] ?? '')));
        $blurb = trim((string) ($input['blurb'] ?? ''));

        if ($title === '' || $blurb === '') {
            throw new \InvalidArgumentException('Title and blurb are required.');
        }

        $editionDate = $this->resolveEditionDate($input['edition_date'] ?? null);
        $isPinned = !empty($input['is_pinned']);
        $publishNow = !empty($input['publish_now']);

        $existing = $this->curatedLinks->findByItem($itemId);
        $existingPublishedAt = $existing['published_at'] ?? null;
        $publishedAt = $publishNow ? date('Y-m-d H:i:s') : $existingPublishedAt;

        $attributes = [
            'item_id' => $itemId,
            'title' => $title,
            'blurb' => $blurb,
            'source_name' => $input['source_name'] ?? ($item['source_name'] ?? null),
            'source_url' => $item['url'] ?? null,
            'is_pinned' => $isPinned,
            'curator_notes' => $input['curator_notes'] ?? null,
            'published_at' => $publishedAt,
        ];

        if ($existing !== null) {
            $this->curatedLinks->update((int) $existing['id'], $attributes);
            $curatedId = (int) $existing['id'];
        } else {
            $curatedId = $this->curatedLinks->create($attributes);
        }

        $edition = $this->editions->ensureForDate($editionDate);
        $this->curatedLinks->detachFromEditions($curatedId);
        $position = $this->curatedLinks->nextPositionForEdition((int) $edition['id']);
        $this->curatedLinks->attachToEdition($curatedId, (int) $edition['id'], $position);

        $this->items->markCurated($itemId);

        return [
            'item' => $this->items->find($itemId),
            'curated' => $this->curatedLinks->find($curatedId),
            'edition' => $edition,
        ];
    }

    private function resolveEditionDate(?string $date): string
    {
        if ($date === null || trim($date) === '') {
            return date('Y-m-d');
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return date('Y-m-d');
        }

        return date('Y-m-d', $timestamp);
    }
}
