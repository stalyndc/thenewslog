<?php

namespace App\Controllers\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Curator;
use Twig\Environment;

class PostController extends AdminController
{
    private Curator $curator;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, ItemRepository $items, FeedRepository $feeds, Curator $curator)
    {
        parent::__construct($view, $auth, $csrf, $items, $feeds);
        $this->curator = $curator;
    }

    public function create(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $this->csrf->validate($request);
            try {
                $result = $this->curator->createPost($request->all());
                $edition = $result['edition'] ?? null;
                $message = 'Post saved successfully.';
                $html = $this->view->render('admin/post_new.twig', [
                    'message' => $message,
                    'form' => $request->all(),
                    'edition' => $edition,
                ]);

                return new Response($html);
            } catch (\Throwable $e) {
                $html = $this->view->render('admin/post_new.twig', [
                    'error' => $e->getMessage(),
                    'form' => $request->all(),
                ]);

                return new Response($html, 422);
            }
        }

        $html = $this->view->render('admin/post_new.twig', [
            'form' => [
                'title' => '',
                'blurb_html' => '',
                'blurb' => '',
                'edition_date' => date('Y-m-d'),
            ],
        ]);

        return new Response($html);
    }
}

