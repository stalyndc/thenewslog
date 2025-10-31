<?php

namespace App\Services;

use App\Repositories\CuratedLinkRepository;
use App\Repositories\FeedRepository;
use App\Repositories\EditionRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TagRepository;
use App\Services\HtmlSanitizer;
use App\Helpers\Encoding;

class Curator
{
    private ItemRepository $items;

    private CuratedLinkRepository $curatedLinks;

    private EditionRepository $editions;

    private TagRepository $tags;

    private FeedRepository $feeds;
    private HtmlSanitizer $sanitizer;

    public function __construct(
        ItemRepository $items,
        CuratedLinkRepository $curatedLinks,
        EditionRepository $editions,
        TagRepository $tags,
        FeedRepository $feeds,
        HtmlSanitizer $sanitizer
    ) {
        $this->items = $items;
        $this->curatedLinks = $curatedLinks;
        $this->editions = $editions;
        $this->tags = $tags;
        $this->feeds = $feeds;
        $this->sanitizer = $sanitizer;
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

        // Normalize incoming text and enforce server-side limits
        $rawTitle = trim((string) ($input['title'] ?? ($item['title'] ?? '')));
        $title = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($rawTitle) ?? $rawTitle) ?? '';
        $title = trim($title);

        $rawBlurb = trim((string) ($input['blurb'] ?? ''));
        $blurb = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($rawBlurb) ?? $rawBlurb) ?? '';
        $blurb = trim($blurb);

        // Optional rich text content from editor
        $blurbHtmlRaw = (string) ($input['blurb_html'] ?? '');
        $blurbHtml = trim($blurbHtmlRaw);

        // Fallback: if plain blurb is empty but HTML is present, derive text from HTML
        if ($blurb === '' && $blurbHtml !== '') {
            $normalized = str_ireplace(['</p>', '<br>', '<br/>', '<br />'], ' ', $blurbHtml);
            $blurb = trim(preg_replace('/\s+/', ' ', strip_tags($normalized)) ?? '');
        }

        if ($title === '' || $blurb === '') {
            throw new \InvalidArgumentException('Title and blurb are required.');
        }

        // Enforce max lengths to avoid DB errors and keep UI concise
        if (strlen($title) > 255) {
            throw new \InvalidArgumentException('Title is too long (max 255 characters).');
        }

        // Enforce word limit (<= 250 words) based on rich text or plain fallback
        $wordSource = $blurbHtml !== '' ? strip_tags($blurbHtml) : $blurb;
        $wordCount = str_word_count($wordSource);
        if ($wordCount > 250) {
            throw new \InvalidArgumentException('Blurb is too long (max 250 words).');
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

        $sourceName = $input['source_name'] ?? ($feedTitle ?? ($item['source_name'] ?? null));
        if (is_string($sourceName)) {
            $sourceName = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($sourceName) ?? $sourceName);
            if (is_string($sourceName) && strlen($sourceName) > 255) {
                $sourceName = substr($sourceName, 0, 255);
            }
        }

        $attributes = [
            'item_id' => $itemId,
            'title' => $title,
            'blurb' => $blurb,
            'blurb_html' => $blurbHtml !== '' ? $this->sanitizer->clean($blurbHtml) : null,
            'source_name' => $sourceName,
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

    /**
     * Create a standalone curated post (not tied to an RSS item).
     *
     * @param array<string, mixed> $input
     * @return array{curated: array<string,mixed>|null, edition: array<string,mixed>|null}
     */
    public function createPost(array $input = []): array
    {
        $rawTitle = trim((string) ($input['title'] ?? ''));
        $title = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($rawTitle) ?? $rawTitle) ?? '';
        $title = trim($title);

        $rawBlurb = trim((string) ($input['blurb'] ?? ''));
        $blurb = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($rawBlurb) ?? $rawBlurb) ?? '';
        $blurb = trim($blurb);
        $blurbHtml = trim((string) ($input['blurb_html'] ?? ''));

        if ($blurb === '' && $blurbHtml !== '') {
            $normalized = str_ireplace(['</p>', '<br>', '<br/>', '<br />'], ' ', $blurbHtml);
            $blurb = trim(preg_replace('/\s+/', ' ', strip_tags($normalized)) ?? '');
        }

        if ($title === '' || ($blurb === '' && $blurbHtml === '')) {
            throw new \InvalidArgumentException('Title and blurb are required.');
        }

        if (strlen($title) > 255) {
            throw new \InvalidArgumentException('Title is too long (max 255 characters).');
        }

        $wordSource = $blurbHtml !== '' ? strip_tags($blurbHtml) : $blurb;
        $wordCount = str_word_count($wordSource);
        if ($wordCount > 250) {
            throw new \InvalidArgumentException('Blurb is too long (max 250 words).');
        }

        $editionDate = $this->resolveEditionDate($input['edition_date'] ?? null);
        $isPinned = !empty($input['is_pinned']);
        $publishNow = !empty($input['publish_now']);
        $publishedAt = $publishNow ? date('Y-m-d H:i:s') : null;

        $sourceName = isset($input['source_name']) ? (string) $input['source_name'] : null;
        if (is_string($sourceName)) {
            $sourceName = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($sourceName) ?? $sourceName);
            if (strlen($sourceName) > 255) {
                $sourceName = substr($sourceName, 0, 255);
            }
        }

        $attributes = [
            'item_id' => null,
            'title' => $title,
            'blurb' => $blurb,
            'blurb_html' => $blurbHtml !== '' ? $this->sanitizer->clean($blurbHtml) : null,
            'source_name' => $sourceName,
            'source_url' => $input['external_url'] ?? null,
            'is_pinned' => $isPinned,
            'curator_notes' => $input['curator_notes'] ?? null,
            'published_at' => $publishedAt,
        ];

        $edition = $this->editions->ensureForDate($editionDate);
        $editionId = (int) $edition['id'];

        $curatedId = $this->curatedLinks->create($attributes);
        $position = $isPinned ? 1 : $this->curatedLinks->positionAfterPinned($editionId);
        $this->curatedLinks->attachToEditionAtPosition($curatedId, $editionId, $position);

        $tags = isset($input['tags']) ? $this->splitTags($input['tags']) : [];
        $this->tags->syncForCuratedLink($curatedId, $tags);

        return [
            'curated' => $this->curatedLinks->find($curatedId),
            'edition' => $edition,
        ];
    }

    /**
     * Update a standalone curated post by curated link id.
     *
     * @param array<string, mixed> $input
     * @return array{curated: array<string,mixed>|null, edition: array<string,mixed>|null}
     */
    public function updatePost(int $curatedId, array $input = []): array
    {
        $existing = $this->curatedLinks->find($curatedId);

        if ($existing === null) {
            throw new \RuntimeException('Post not found.');
        }

        $rawTitle = trim((string) ($input['title'] ?? ($existing['title'] ?? '')));
        $title = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($rawTitle) ?? $rawTitle) ?? '';
        $title = trim($title);

        $rawBlurb = trim((string) ($input['blurb'] ?? ($existing['blurb'] ?? '')));
        $blurb = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($rawBlurb) ?? $rawBlurb) ?? '';
        $blurb = trim($blurb);
        $blurbHtml = trim((string) ($input['blurb_html'] ?? ($existing['blurb_html'] ?? '')));

        if ($blurb === '' && $blurbHtml !== '') {
            $normalized = str_ireplace(['</p>', '<br>', '<br/>', '<br />'], ' ', $blurbHtml);
            $blurb = trim(preg_replace('/\s+/', ' ', strip_tags($normalized)) ?? '');
        }

        if ($title === '' || ($blurb === '' && $blurbHtml === '')) {
            throw new \InvalidArgumentException('Title and blurb are required.');
        }

        if (strlen($title) > 255) {
            throw new \InvalidArgumentException('Title is too long (max 255 characters).');
        }

        $wordSource = $blurbHtml !== '' ? strip_tags($blurbHtml) : $blurb;
        $wordCount = str_word_count($wordSource);
        if ($wordCount > 250) {
            throw new \InvalidArgumentException('Blurb is too long (max 250 words).');
        }

        $editionDate = $this->resolveEditionDate($input['edition_date'] ?? null);
        $isPinned = !empty($input['is_pinned']);
        $publishNow = !empty($input['publish_now']);
        $existingPublishedAt = $existing['published_at'] ?? null;
        $publishedAt = $publishNow ? date('Y-m-d H:i:s') : $existingPublishedAt;

        $sourceName = isset($input['source_name']) ? (string) $input['source_name'] : ($existing['source_name'] ?? null);
        if (is_string($sourceName)) {
            $sourceName = Encoding::decodeHtmlEntities(Encoding::ensureUtf8($sourceName) ?? $sourceName);
            if (strlen((string) $sourceName) > 255) {
                $sourceName = substr((string) $sourceName, 0, 255);
            }
        }

        $attributes = [
            'title' => $title,
            'blurb' => $blurb,
            'blurb_html' => $blurbHtml !== '' ? $this->sanitizer->clean($blurbHtml) : null,
            'source_name' => $sourceName,
            'source_url' => $input['external_url'] ?? ($existing['source_url'] ?? null),
            'is_pinned' => $isPinned,
            'curator_notes' => $input['curator_notes'] ?? ($existing['curator_notes'] ?? null),
            'published_at' => $publishedAt,
        ];

        $edition = $this->editions->ensureForDate($editionDate);
        $editionId = (int) $edition['id'];

        $this->curatedLinks->update($curatedId, $attributes);

        // Manage pivot/position relative to edition and pinning
        $previousPivot = $this->curatedLinks->pivotForCuratedLink($curatedId);
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

        $tags = isset($input['tags']) ? $this->splitTags($input['tags']) : [];
        $this->tags->syncForCuratedLink($curatedId, $tags);

        return [
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
