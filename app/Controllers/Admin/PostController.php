<?php

namespace App\Controllers\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\TagRepository;
use App\Repositories\EditionRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Curator;
use Twig\Environment;

class PostController extends AdminController
{
    private Curator $curator;
    private CuratedLinkRepository $curatedLinks;
    private TagRepository $tags;
    private EditionRepository $editions;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, ItemRepository $items, FeedRepository $feeds, Curator $curator, CuratedLinkRepository $curatedLinks, TagRepository $tags, EditionRepository $editions, \Psr\Log\LoggerInterface $logger = null)
    {
        parent::__construct($view, $auth, $csrf, $items, $feeds, $logger);
        $this->curator = $curator;
        $this->curatedLinks = $curatedLinks;
        $this->tags = $tags;
        $this->editions = $editions;
    }

    public function create(Request $request): Response
    {
        if ($request->method() === 'POST') {
            // Validate CSRF token extracted from request body or headers.
            $this->csrf->assertValid($this->csrf->extractToken($request));
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

    public function destroy(Request $request, int $id): Response
    {
        $this->csrf->assertValid($this->csrf->extractToken($request));

        try {
            $this->curatedLinks->delete($id);

            // Cleanup unused tags (best‑effort)
            try { $this->tags->deleteOrphans(); } catch (\Throwable) {}

            $back = $request->header('Referer');
            if (!is_string($back) || $back === '') {
                $back = '/admin/inbox?flash=deleted';
            }

            return Response::redirect($back);
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('PostController::destroy failed', [
                    'curated_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }

            return new Response('Failed to delete post.', 500);
        }
    }

    public function edit(Request $request, int $id): Response
    {
        $curated = $this->curatedLinks->find($id);
        if ($curated === null) {
            return new Response('Not found', 404);
        }

        if ($request->method() === 'POST') {
            $this->csrf->assertValid($this->csrf->extractToken($request));

            $payload = [
                'title' => $request->input('title'),
                'blurb' => $request->input('blurb'),
                'blurb_html' => $request->input('blurb_html'),
                'edition_date' => $request->input('edition_date'),
                'is_pinned' => $request->input('is_pinned') === '1',
                'publish_now' => $request->input('publish_now') === '1',
                'tags' => $request->input('tags'),
            ];

            try {
                $result = $this->curator->updatePost($id, $payload);
                $edition = $result['edition'] ?? null;
                $html = $this->view->render('admin/post_edit.twig', [
                    'message' => 'Post updated successfully.',
                    'form' => $payload,
                    'edition' => $edition,
                    'curated' => $result['curated'] ?? $curated,
                ]);
                return new Response($html);
            } catch (\InvalidArgumentException $e) {
                $html = $this->view->render('admin/post_edit.twig', [
                    'error' => $e->getMessage(),
                    'form' => $payload,
                    'curated' => $curated,
                ]);
                return new Response($html, 422);
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->error('PostController::edit failed', [
                        'error' => $e->getMessage(),
                        'curated_id' => $id,
                    ]);
                }
                $html = $this->view->render('admin/post_edit.twig', [
                    'error' => 'An unexpected error occurred: ' . $e->getMessage(),
                    'form' => $payload,
                    'curated' => $curated,
                ]);
                return new Response($html, 500);
            }
        }

        $edition = $this->editions->findByCuratedLink($id);
        $tagMap = $this->tags->tagsForCuratedLinks([$id]);
        $tags = $tagMap[$id] ?? [];
        $tagsCsv = '';
        foreach ($tags as $t) {
            if (is_array($t) && isset($t['name'])) {
                $name = (string) $t['name'];
                if ($name !== '') {
                    $tagsCsv .= ($tagsCsv === '' ? '' : ', ') . $name;
                }
            }
        }

        $form = [
            'title' => (string) ($curated['title'] ?? ''),
            'blurb' => (string) ($curated['blurb'] ?? ''),
            'blurb_html' => (string) ($curated['blurb_html'] ?? ''),
            'edition_date' => (string) (($edition['edition_date'] ?? date('Y-m-d'))),
            'is_pinned' => ((int) ($curated['is_pinned'] ?? 0)) === 1,
            'publish_now' => false,
            'tags' => $tagsCsv,
        ];

        $html = $this->view->render('admin/post_edit.twig', [
            'form' => $form,
            'curated' => $curated,
            'edition' => $edition,
        ]);

        return new Response($html);
    }
}
