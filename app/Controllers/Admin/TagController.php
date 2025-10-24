<?php

namespace App\Controllers\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TagRepository;
use App\Services\Auth;
use App\Services\Csrf;
use Twig\Environment;

class TagController extends AdminController
{
    private TagRepository $tags;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, TagRepository $tags, ItemRepository $items, FeedRepository $feeds)
    {
        parent::__construct($view, $auth, $csrf, $items, $feeds);
        $this->tags = $tags;
    }

    public function suggest(Request $request): Response
    {
        $queryRaw = (string) $request->query('tags', '');
        $fullRaw = (string) $request->query('tags_full', $queryRaw);
        $existingRaw = (string) $request->query('existing', '');

        $fullParts = array_map('trim', explode(',', $fullRaw));
        $active = trim((string) array_pop($fullParts));

        if ($existingRaw !== '') {
            $fullParts = array_merge($fullParts, array_map('trim', explode(',', $existingRaw)));
        }

        $existingMap = [];

        foreach ($fullParts as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $key = mb_strtolower($value);
            if (isset($existingMap[$key])) {
                continue;
            }

            $existingMap[$key] = true;
        }

        $term = $active !== '' ? $active : trim($queryRaw);

        if ($term === '' && !empty($fullParts)) {
            $term = trim((string) end($fullParts));
        }

        $suggestions = $term === '' ? [] : $this->tags->search($term, 8);

        $filtered = [];

        foreach ($suggestions as $tag) {
            $name = $tag['name'] ?? null;
            if (!is_string($name)) {
                continue;
            }

            $key = mb_strtolower($name);
            if (isset($existingMap[$key])) {
                continue;
            }

            $filtered[] = $tag;
        }

        return $this->render('admin/partials/tag_suggestions.twig', [
            'suggestions' => array_slice($filtered, 0, 8),
        ]);
    }

    public function validate(Request $request): Response
    {
        $value = (string) ($request->query('tags') ?? $request->input('tags', ''));
        $raw = array_map('trim', explode(',', $value));
        $nonEmpty = array_values(array_filter($raw, static fn ($tag) => $tag !== ''));

        $unique = [];
        $seen = [];

        foreach ($nonEmpty as $tag) {
            $key = mb_strtolower($tag);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $tag;
        }

        $duplicates = count($nonEmpty) - count($unique);

        $message = '';
        $variant = '';

        if ($duplicates > 0) {
            $message = sprintf('%d duplicate tag%s will be ignored on save.', $duplicates, $duplicates === 1 ? '' : 's');
            $variant = 'warn';
        } elseif (count($unique) > 6) {
            $message = 'Consider keeping editions under six tags for clarity.';
            $variant = 'info';
        } elseif (!empty($unique)) {
            $message = 'Tags look good.';
            $variant = 'success';
        } else {
            $message = '';
        }

        return $this->render('admin/partials/tag_feedback.twig', [
            'message' => $message,
            'variant' => $variant,
        ]);
    }
}
