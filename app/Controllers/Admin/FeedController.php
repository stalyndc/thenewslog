<?php

namespace App\Controllers\Admin;

use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Services\Auth;
use Twig\Environment;

class FeedController extends AdminController
{
    private FeedRepository $feeds;

    public function __construct(Environment $view, Auth $auth, FeedRepository $feeds)
    {
        parent::__construct($view, $auth);
        $this->feeds = $feeds;
    }

    public function index(): Response
    {
        try {
            $feeds = $this->feeds->all();
        } catch (\Throwable $exception) {
            $feeds = [];
        }

        return $this->render('admin/feeds.twig', [
            'feeds' => $feeds,
        ]);
    }
}
