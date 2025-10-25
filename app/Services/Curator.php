<?php

namespace App\Services;

use App\Repositories\CuratedLinkRepository;
use App\Repositories\FeedRepository;
use App\Repositories\EditionRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TagRepository;

class Curator
{
    private ItemRepository $items;

    private CuratedLinkRepository $curatedLinks;

    private EditionRepository $editions;

    private TagRepository $tags;

    private FeedRepository $feeds;

    public function __construct(
        ItemRepository $items,
        CuratedLinkRepository $curatedLinks,
        EditionRepository $editions,
        TagRepository $tags,
        FeedRepository $feeds
    ) {
        $this->items = $items;
        $this->curatedLinks = $curatedLinks;
        $this->editions = $editions;
        $this->tags = $tags;
        $this->feeds = $feeds;
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

        // Prefer the feed's human-edited title for source_name when available
        $feedTitle = null;
        $feedId = (int) ($item['feed_id'] ?? 0);
        if ($feedId > 0) {
            try {
                $feed = $this->feeds->find($feedId);
                if (is_array($feed) && !empty($feed['title'])) {
                    $feedTitle = (string) $feed['title'];
                }
            } catch (\Throwable) {
                // ignore; fall back to item source name
            }
        }

        $attributes = [
            'item_id' => $itemId,
            'title' => $title,
            'blurb' => $blurb,
            'source_name' => $input['source_name'] ?? ($feedTitle ?? ($item['source_name'] ?? null)),
            'source_url' => $item['url'] ?? null,
            'is_pinned' => $isPinned,
            'curator_notes' => $input['curator_notes'] ?? null,
            'published_at' => $publishedAt,
        ];

        $edition = $this->editions->ensureForDate($editionDate);
        $editionId = (int) $edition['id'];

        if ($existing !== null) {
            $curatedId = (int) $existing['id'];
            $previousPivot = $this->curatedLinks->pivotForCuratedLink($curatedId);

            $this->curatedLinks->update($curatedId, $attributes);

            $previousEditionId = $previousPivot['edition_id'] ?? null;

            if ($previousEditionId === null || (int) $previousEditionId !== $editionId) {
                if ($previousEditionId !== null) {
                    $this->curatedLinks->detachFromEdition((int) $previousEditionId, $curatedId);
                }

                $position = $isPinned ? 1 : $this->curatedLinks->positionAfterPinned($editionId);
                $this->curatedLinks->attachToEditionAtPosition($curatedId, $editionId, $position);
            } elseif ($isPinned) {
                $this->curatedLinks->moveToTopOfEdition($curatedId, $editionId);
            }
        } else {
            $curatedId = $this->curatedLinks->create($attributes);
            $position = $isPinned ? 1 : $this->curatedLinks->positionAfterPinned($editionId);
            $this->curatedLinks->attachToEditionAtPosition($curatedId, $editionId, $position);
        }

        $tags = isset($input['tags']) ? $this->splitTags($input['tags']) : [];
        $this->tags->syncForCuratedLink($curatedId, $tags);

        $this->items->markCurated($itemId);

        return [
            'item' => $this->items->find($itemId),
            'curated' => $this->curatedLinks->find($curatedId),
            'edition' => $edition,
        ];
    }

    private function splitTags(null|string|array $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return $this->validateTags($value);
        }

        $parts = array_map('trim', explode(',', $value));
        $filtered = array_filter($parts, static fn (string $tag): bool => $tag !== '');

        return $this->validateTags($filtered);
    }

    /**
     * @param array<int|string, mixed> $tags
     * @return array<int, string>
     */
    private function validateTags(array $tags): array
    {
        $validated = [];

        foreach ($tags as $tag) {
            if (!is_string($tag) && !is_int($tag)) {
                continue;
            }

            $tagStr = trim((string) $tag);

            if ($tagStr === '' || strlen($tagStr) > 100) {
                continue;
            }

            $validated[] = $tagStr;
        }

        return array_unique($validated);
    }

    private function resolveEditionDate(?string $date): string
    {
        if ($date === null || trim($date) === '') {
            return date('Y-m-d');
        }

        $trimmed = trim($date);

        if (strlen($trimmed) > 10) {
            return date('Y-m-d');
        }

        $timestamp = strtotime($trimmed);

        if ($timestamp === false) {
            return date('Y-m-d');
        }

        return date('Y-m-d', $timestamp);
    }
}
