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

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, ItemRepository $items, FeedRepository $feeds, Curator $curator, \Psr\Log\LoggerInterface $logger = null)
    {
        parent::__construct($view, $auth, $csrf, $items, $feeds, $logger);
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
            } catch (\InvalidArgumentException $e) {
                // Validation errors - show to user
                $html = $this->view->render('admin/post_new.twig', [
                    'error' => $e->getMessage(),
                    'form' => $request->all(),
                ]);
                return new Response($html, 422);
            } catch (\Throwable $e) {
                // Unexpected errors - log and show generic message
                if ($this->logger) {
                    $this->logger->error('PostController::create failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
                $html = $this->view->render('admin/post_new.twig', [
                    'error' => 'An unexpected error occurred: ' . $e->getMessage(),
                    'form' => $request->all(),
                ]);
                return new Response($html, 500);
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

