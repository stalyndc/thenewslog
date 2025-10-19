<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Response;
use App\Repositories\FeedRepository;
use Twig\Environment;

class FeedController extends BaseController
{
    private FeedRepository $feeds;

    public function __construct(Environment $view, FeedRepository $feeds)
    {
        parent::__construct($view);
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
