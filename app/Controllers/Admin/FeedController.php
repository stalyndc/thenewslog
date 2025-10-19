<?php

namespace App\Controllers\Admin;

use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use Twig\Environment;

class FeedController extends AdminController
{
    private FeedRepository $feeds;

    public function __construct(Environment $view, Auth $auth, FeedRepository $feeds, ItemRepository $items)
    {
        parent::__construct($view, $auth, $items, $feeds);
        $this->feeds = $feeds;
    }

    public function index(): Response
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;

        try {
            $feeds = $this->feeds->all($page, $perPage);
        } catch (\Throwable $exception) {
            $feeds = [];
        }

        $total = $this->feeds->countAll();
        $totalPages = max(1, (int) ceil(max(1, $total) / $perPage));

        return $this->render('admin/feeds.twig', $this->withAdminMetrics([
            'feeds' => $feeds,
            'page' => $page,
            'total_pages' => $totalPages,
        ]));
    }
}
