<?php

namespace App\Controllers\Admin;

use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use Twig\Environment;

class InboxController extends AdminController
{
    private ItemRepository $items;

    private FeedRepository $feeds;

    public function __construct(Environment $view, Auth $auth, ItemRepository $items, FeedRepository $feeds)
    {
        parent::__construct($view, $auth, $items, $feeds);
        $this->items = $items;
        $this->feeds = $feeds;
    }

    public function index(): Response
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $feedId = isset($_GET['feed_id']) && $_GET['feed_id'] !== '' ? (int) $_GET['feed_id'] : null;

        try {
            $inbox = $this->items->inbox($perPage, $page, $feedId);
        } catch (\Throwable $exception) {
            $inbox = [];
        }

        $total = $this->items->countNew($feedId);
        $totalPages = max(1, (int) ceil(max(1, $total) / $perPage));

        foreach ($inbox as &$item) {
            $timestamp = $item['published_at'] ?? $item['created_at'] ?? null;
            $item['published_relative'] = $timestamp ? $this->formatRelative($timestamp) : null;
        }
        unset($item);

        $feedOptions = $this->feeds->active();

        return $this->render('admin/inbox.twig', $this->withAdminMetrics([
            'items' => $inbox,
            'page' => $page,
            'total_pages' => $totalPages,
            'feeds' => $feedOptions,
            'selected_feed_id' => $feedId,
        ]));
    }
}
