<?php

namespace App\Controllers\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TagRepository;
use App\Services\Auth;
use App\Services\Curator;
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
        ItemRepository $items,
        CuratedLinkRepository $curatedLinks,
        EditionRepository $editions,
        Curator $curator,
        TagRepository $tags
    ) {
        parent::__construct($view, $auth);
        $this->items = $items;
        $this->curatedLinks = $curatedLinks;
        $this->editions = $editions;
        $this->curator = $curator;
        $this->tags = $tags;
    }

    public function show(int $id): Response
    {
        $item = $this->safeFindItem($id);
        $curated = $this->resolveCuratedFromItem($item);
        $edition = $curated ? $this->editions->findByCuratedLink((int) $curated['id']) : null;
        $existingTags = [];
        if ($curated) {
            $tagMap = $this->tags->tagsForCuratedLinks([(int) $curated['id']]);
            $existingTags = $tagMap[(int) $curated['id']] ?? [];
        }

        $form = $this->buildFormState($item, $curated, null, $existingTags);

        return $this->render('admin/curate.twig', [
            'item' => $item,
            'curated' => $curated,
            'edition' => $edition,
            'form' => $form,
        ]);
    }

    public function store(Request $request, int $id): Response
    {
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
            $error = 'Something went wrong while saving the curated link.';
        }

        $item = $result['item'] ?? $this->safeFindItem($id);
        $curated = $result['curated'] ?? $this->resolveCuratedFromItem($item);
        $edition = $result['edition'] ?? ($curated ? $this->editions->findByCuratedLink((int) $curated['id']) : null);
        $existingTags = [];
        if ($curated) {
            $tagMap = $this->tags->tagsForCuratedLinks([(int) $curated['id']]);
            $existingTags = $tagMap[(int) $curated['id']] ?? [];
        }

        $form = $this->buildFormState($item, $curated, $payload, $existingTags);

        return $this->render('admin/curate.twig', [
            'item' => $item,
            'curated' => $curated,
            'edition' => $edition,
            'form' => $form,
            'message' => $message,
            'error' => $error,
        ]);
    }

    private function resolveCuratedFromItem(?array $item): ?array
    {
        if ($item === null) {
            return null;
        }

        try {
            return $this->curatedLinks->findByItem($item['id']);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function buildFormState(?array $item, ?array $curated, ?array $payload, array $existingTags): array
    {
        $payload = $payload ?? [];
        $defaultDate = date('Y-m-d');

        if ($curated) {
            $existingEdition = $this->editions->findByCuratedLink((int) $curated['id']);
            $defaultDate = $existingEdition['edition_date'] ?? $defaultDate;
        }

        return [
            'title' => $payload['title'] ?? ($curated['title'] ?? ($item['title'] ?? '')),
            'blurb' => $payload['blurb'] ?? ($curated['blurb'] ?? ''),
            'edition_date' => $payload['edition_date'] ?? $defaultDate,
            'is_pinned' => (bool) ($payload['is_pinned'] ?? ($curated['is_pinned'] ?? false)),
            'publish_now' => (bool) ($payload['publish_now'] ?? false),
            'tags' => $this->tagsToString($payload['tags'] ?? null, $existingTags),
        ];
    }

    private function safeFindItem(int $id): ?array
    {
        try {
            return $this->items->find($id);
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
            return $payloadTags;
        }

        if (is_array($payloadTags)) {
            return implode(', ', $payloadTags);
        }

        if (!empty($existingTags)) {
            return implode(', ', array_map(static fn ($tag) => $tag['name'], $existingTags));
        }

        return '';
    }
}
