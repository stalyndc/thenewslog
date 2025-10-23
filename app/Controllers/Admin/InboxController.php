<?php

namespace App\Controllers\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use App\Services\Csrf;
use Twig\Environment;

class InboxController extends AdminController
{
    private ItemRepository $items;

    private FeedRepository $feeds;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, ItemRepository $items, FeedRepository $feeds)
    {
        parent::__construct($view, $auth, $csrf, $items, $feeds);
        $this->items = $items;
        $this->feeds = $feeds;
    }

    public function index(Request $request): Response
    {
        $context = $this->buildContext($request);

        $flash = $request->query('flash');
        if ($flash) {
            $context['message'] = match ($flash) {
                'fetched' => 'Feeds refreshed. Check the inbox for new items.',
                default => null,
            };
        }

        $error = $request->query('error');
        if ($error) {
            $context['error'] = match ($error) {
                'fetch_failed' => 'Unable to refresh feeds right now. Please try again.',
                default => null,
            };
        }

        return $this->render('admin/inbox.twig', $this->withAdminMetrics($context));
    }

    public function partial(Request $request): Response
    {
        $context = $this->buildContext($request);

        $response = $this->render('admin/partials/inbox_table.twig', $context);
        $response->setHeader('HX-Trigger', json_encode([
            'inbox:updated' => [
                'latest_id' => $context['latest_id'] ?? 0,
                'count' => $context['total_count'] ?? 0,
            ],
        ]));

        return $response;
    }

    public function poll(Request $request): Response
    {
        $afterId = (int) $request->query('after_id', 0);
        $feedParam = $request->query('feed_id');
        $feedId = ($feedParam !== null && $feedParam !== '') ? (int) $feedParam : null;

        if ($afterId <= 0) {
            return new Response('', 204);
        }

        $items = $this->items->inboxAfter($afterId, 25, $feedId);

        if (empty($items)) {
            return new Response('', 204);
        }

        foreach ($items as &$item) {
            $timestamp = $item['published_at'] ?? $item['created_at'] ?? null;
            $item['published_relative'] = $timestamp ? $this->formatRelative($timestamp) : null;
        }
        unset($item);

        $total = $this->items->countNew($feedId);
        $latestId = (int) ($items[0]['id'] ?? $afterId);

        $html = $this->view->render('admin/partials/inbox_rows.twig', [
            'items' => $items,
        ]);

        $response = new Response($html);
        $response->setHeader('Content-Type', 'text/html; charset=utf-8');
        $response->setHeader('HX-Trigger', json_encode([
            'inbox:updated' => [
                'latest_id' => $latestId,
                'count' => $total,
            ],
        ]));

        return $response;
    }

    public function delete(Request $request): Response
    {
        $guard = $this->guardCsrf($request);

        if ($guard !== null) {
            return $guard;
        }

        $id = (int) $request->input('id', 0);

        if ($id > 0) {
            $this->items->delete($id);
        }

        $response = new Response('', 200);
        $response->setHeader('HX-Refresh', 'true');

        return $response;
    }

    public function ignore(Request $request): Response
    {
        $guard = $this->guardCsrf($request);

        if ($guard !== null) {
            return $guard;
        }

        $id = (int) $request->input('id', 0);

        if ($id > 0) {
            try {
                $this->items->updateStatus($id, 'discarded');
            } catch (\Throwable $exception) {
                $response = new Response('', 200);
                $response->setHeader('HX-Refresh', 'true');

                return $response;
            }
        }

        $response = new Response('', 200);
        $response->setHeader('HX-Refresh', 'true');

        return $response;
    }

    private function buildContext(Request $request): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $feedParam = $request->query('feed_id');
        $feedId = ($feedParam !== null && $feedParam !== '') ? (int) $feedParam : null;

        try {
            $items = $this->items->inbox($perPage, $page, $feedId);
        } catch (\Throwable $exception) {
            $items = [];
        }

        $total = $this->items->countNew($feedId);
        $totalPages = max(1, (int) ceil(max(1, $total) / $perPage));
        $latestId = isset($items[0]['id']) ? (int) $items[0]['id'] : 0;

        foreach ($items as &$item) {
            $timestamp = $item['published_at'] ?? $item['created_at'] ?? null;
            $item['published_relative'] = $timestamp ? $this->formatRelative($timestamp) : null;
        }
        unset($item);

        return [
            'items' => $items,
            'page' => $page,
            'total_pages' => $totalPages,
            'feeds' => $this->feeds->active(),
            'selected_feed_id' => $feedId,
            'latest_id' => $latestId,
            'total_count' => $total,
        ];
    }
}
