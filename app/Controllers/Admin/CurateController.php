<?php

namespace App\Controllers\Admin;

use App\Helpers\Encoding;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TagRepository;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Curator;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class CurateController extends AdminController
{
    private ItemRepository $items;

    private CuratedLinkRepository $curatedLinks;

    private EditionRepository $editions;

    private Curator $curator;

    private TagRepository $tags;

    public function __construct(
        Environment $view,
        Auth $auth,
        Csrf $csrf,
        ItemRepository $items,
        CuratedLinkRepository $curatedLinks,
        EditionRepository $editions,
        Curator $curator,
        TagRepository $tags,
        FeedRepository $feeds,
        LoggerInterface $logger
    ) {
        parent::__construct($view, $auth, $csrf, $items, $feeds, $logger);
        $this->items = $items;
        $this->curatedLinks = $curatedLinks;
        $this->editions = $editions;
        $this->curator = $curator;
        $this->tags = $tags;
    }

    public function show(int $id): Response
    {
        try {
            $item = $this->safeFindItem($id);

            if ($item === null) {
                $this->log('warning', 'Curate page requested for non-existent item', ['item_id' => $id]);
                return Response::redirect('/admin/inbox?flash=missing');
            }

            $curated = $this->resolveCuratedFromItem($item);
            $edition = null;
            $existingTags = [];

            if ($curated) {
                try {
                    $edition = $this->sanitizeEdition($this->editions->findByCuratedLink((int) $curated['id']));
                } catch (\Throwable $e) {
                    $this->log('error', 'Failed to fetch edition for curated link', ['curated_id' => $curated['id'], 'error' => $e->getMessage()]);
                }

                try {
                    $tagMap = $this->tags->tagsForCuratedLinks([(int) $curated['id']]);
                    $existingTags = $this->sanitizeTags($tagMap[(int) $curated['id']] ?? []);
                } catch (\Throwable $e) {
                    $this->log('error', 'Failed to fetch tags for curated link', ['curated_id' => $curated['id'], 'error' => $e->getMessage()]);
                }
            }

            $form = $this->buildFormState($item, $curated, null, $existingTags);

            return $this->render('admin/curate.twig', $this->withAdminMetrics([
                'item' => $item,
                'curated' => $curated,
                'edition' => $edition,
                'form' => $form,
            ]));
        } catch (\Throwable $e) {
            $this->log('error', 'CurateController::show() failed', ['item_id' => $id, 'error' => $e->getMessage()]);
            return Response::redirect('/admin/inbox?flash=error');
        }
    }

    public function store(Request $request, int $id): Response
    {
        $guard = $this->guardCsrf($request);

        if ($guard !== null) {
            return $guard;
        }

        $payload = [
            'title' => $this->trimOrNull($request->input('title')),
            'blurb' => $this->trimOrNull($request->input('blurb')),
            'edition_date' => $request->input('edition_date'),
            'is_pinned' => $request->input('is_pinned') === '1',
            'publish_now' => $request->input('publish_now') === '1',
            'tags' => $request->input('tags'),
        ];

        $message = null;
        $error = null;
        $result = null;

        try {
            $result = $this->curator->curate($id, $payload);
            $message = 'Curated link saved successfully.';
        } catch (\InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (\Throwable $exception) {
            $this->logger->error('CurateController::store failed', [
                'error' => $exception->getMessage(),
                'item_id' => $id,
            ]);
            $error = 'Something went wrong while saving the curated link. Please try again.';
        }

        $item = $this->sanitizeItem($result['item'] ?? $this->safeFindItem($id));
        $curated = $this->sanitizeCurated($result['curated'] ?? $this->resolveCuratedFromItem($item));
        $edition = $this->sanitizeEdition($result['edition'] ?? ($curated ? $this->editions->findByCuratedLink((int) $curated['id']) : null));
        $existingTags = [];
        if ($curated) {
            $tagMap = $this->tags->tagsForCuratedLinks([(int) $curated['id']]);
            $existingTags = $this->sanitizeTags($tagMap[(int) $curated['id']] ?? []);
        }

        $form = $this->buildFormState($item, $curated, $payload, $existingTags);

        return $this->render('admin/curate.twig', $this->withAdminMetrics([
            'item' => $item,
            'curated' => $curated,
            'edition' => $edition,
            'form' => $form,
            'message' => $message,
            'error' => $error,
        ]));
    }

    public function destroy(Request $request, int $id): Response
    {
        $guard = $this->guardCsrf($request);

        if ($guard !== null) {
            return $guard;
        }

        $item = $this->safeFindItem($id);

        if ($item === null) {
            return Response::redirect('/admin/inbox?flash=missing');
        }

        $curated = $this->curatedLinks->findByItem($item['id']);

        if ($curated !== null) {
            $this->curatedLinks->delete((int) $curated['id']);
        }

        $this->items->delete($item['id']);

        // Remove any tags that no longer have links after deletion
        try {
            $this->tags->deleteOrphans();
        } catch (\Throwable $e) {
            // Non-fatal; continue redirect
            $this->log('warning', 'Failed to cleanup orphan tags after delete', ['error' => $e->getMessage()]);
        }

        return Response::redirect('/admin/inbox?flash=deleted');
    }

    private function resolveCuratedFromItem(?array $item): ?array
    {
        if ($item === null) {
            return null;
        }

        try {
            return $this->sanitizeCurated($this->curatedLinks->findByItem($item['id']));
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function buildFormState(?array $item, ?array $curated, ?array $payload, array $existingTags): array
    {
        $payload = $payload ?? [];
        $defaultDate = date('Y-m-d');

        if ($curated) {
            $existingEdition = $this->sanitizeEdition($this->editions->findByCuratedLink((int) $curated['id']));
            $defaultDate = $existingEdition['edition_date'] ?? $defaultDate;
        }

        $titleSource = $payload['title'] ?? ($curated['title'] ?? ($item['title'] ?? ''));
        $blurbSource = $payload['blurb'] ?? ($curated['blurb'] ?? '');
        $editionDateSource = $payload['edition_date'] ?? $defaultDate;

        $title = Encoding::ensureUtf8(is_string($titleSource) ? $titleSource : (string) $titleSource) ?? '';
        $blurb = Encoding::ensureUtf8(is_string($blurbSource) ? $blurbSource : (string) $blurbSource) ?? '';
        $editionDate = Encoding::ensureUtf8(is_string($editionDateSource) ? $editionDateSource : (string) $editionDateSource) ?? '';

        if ($editionDate === '') {
            $editionDate = $defaultDate;
        }

        return [
            'title' => $title,
            'blurb' => $blurb,
            'edition_date' => $editionDate,
            'is_pinned' => (bool) ($payload['is_pinned'] ?? (((int) ($curated['is_pinned'] ?? 0)) === 1)),
            'publish_now' => (bool) ($payload['publish_now'] ?? false),
            'tags' => Encoding::ensureUtf8($this->tagsToString($payload['tags'] ?? null, $existingTags)) ?? '',
        ];
    }

    private function safeFindItem(int $id): ?array
    {
        try {
            return $this->sanitizeItem($this->items->find($id));
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function trimOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function tagsToString(null|string|array $payloadTags, array $existingTags): string
    {
        if (is_string($payloadTags)) {
            return Encoding::ensureUtf8($payloadTags) ?? '';
        }

        if (is_array($payloadTags)) {
            $sanitized = [];

            foreach ($payloadTags as $tag) {
                if (!is_string($tag) && !is_int($tag)) {
                    continue;
                }

                $clean = Encoding::ensureUtf8((string) $tag) ?? '';

                if ($clean !== '') {
                    $sanitized[] = $clean;
                }
            }

            return implode(', ', $sanitized);
        }

        if (!empty($existingTags)) {
            $names = [];

            foreach ($existingTags as $tag) {
                if (!is_array($tag) || !isset($tag['name']) || !is_string($tag['name'])) {
                    continue;
                }

                $clean = Encoding::ensureUtf8($tag['name']) ?? '';

                if ($clean !== '') {
                    $names[] = $clean;
                }
            }

            return implode(', ', $names);
        }

        return '';
    }

    private function sanitizeItem(?array $item): ?array
    {
        if ($item === null) {
            return null;
        }

        foreach (['title', 'feed_title', 'source_name', 'summary_raw', 'author'] as $field) {
            if (isset($item[$field]) && is_string($item[$field])) {
                $item[$field] = Encoding::ensureUtf8($item[$field]) ?? '';
            }
        }

        if (isset($item['url']) && is_string($item['url'])) {
            $item['url'] = Encoding::ensureUtf8($item['url']) ?? '';
        }

        return $item;
    }

    private function sanitizeCurated(?array $curated): ?array
    {
        if ($curated === null) {
            return null;
        }

        foreach (['title', 'blurb', 'source_name', 'source_url', 'curator_notes', 'tags_csv'] as $field) {
            if (isset($curated[$field]) && is_string($curated[$field])) {
                $curated[$field] = Encoding::ensureUtf8($curated[$field]) ?? '';
            }
        }

        return $curated;
    }

    private function sanitizeEdition(?array $edition): ?array
    {
        if ($edition === null) {
            return null;
        }

        foreach (['edition_date', 'status', 'title', 'intro'] as $field) {
            if (isset($edition[$field]) && is_string($edition[$field])) {
                $edition[$field] = Encoding::ensureUtf8($edition[$field]) ?? '';
            }
        }

        return $edition;
    }

    /**
     * @param array<int, array<string, mixed>> $tags
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeTags(array $tags): array
    {
        foreach ($tags as &$tag) {
            if (!is_array($tag)) {
                continue;
            }

            if (isset($tag['name']) && is_string($tag['name'])) {
                $tag['name'] = Encoding::ensureUtf8($tag['name']) ?? '';
            }

            if (isset($tag['slug']) && is_string($tag['slug'])) {
                $tag['slug'] = Encoding::ensureUtf8($tag['slug']) ?? '';
            }
        }

        unset($tag);

        return $tags;
    }
}
