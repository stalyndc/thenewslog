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
        try {
            $feeds = $this->feeds->all();
        } catch (\Throwable $exception) {
            $feeds = [];
        }

        return $this->render('admin/feeds.twig', $this->withAdminMetrics([
            'feeds' => $feeds,
        ]));
    }
}
