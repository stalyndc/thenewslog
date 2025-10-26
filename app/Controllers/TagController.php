<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\TagRepository;
use Twig\Environment;
use App\Services\Auth;

class TagController extends BaseController
{
    private TagRepository $tags;

    private CuratedLinkRepository $curatedLinks;

    private Auth $auth;

    public function __construct(Environment $view, TagRepository $tags, CuratedLinkRepository $curatedLinks, Auth $auth)
    {
        parent::__construct($view);
        $this->tags = $tags;
        $this->curatedLinks = $curatedLinks;
        $this->auth = $auth;
    }

    public function index(): Response
    {
        $tags = $this->tags->allWithCounts();

        $canonical = rtrim($this->baseUrl(), '/') . '/tags';
        $html = $this->view->render('tags.twig', [
            'current_nav' => 'tags',
            'tags' => $tags,
            'is_admin' => $this->auth->check(),
            'canonical_url' => $canonical,
        ]);

        return Response::cached($html, 600, true);
    }

    public function show(Request $request, string $slug): Response
    {
        $tag = $this->tags->findBySlug($slug);

        if ($tag === null) {
            return Response::redirect('/tags');
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $items = $this->curatedLinks->streamForTag((int) $tag['id'], $page, $perPage);
        $tagsMap = $this->tags->tagsForCuratedLinks(array_column($items, 'id'));
        $total = $this->curatedLinks->streamCountForTag((int) $tag['id']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $canonical = rtrim($this->baseUrl(), '/') . '/tags/' . rawurlencode((string) $tag['slug']);
        $html = $this->view->render('tag.twig', [
            'current_nav' => 'tags',
            'tag' => $tag,
            'items' => $items,
            'tagsByLink' => $tagsMap,
            'page' => $page,
            'total_pages' => $totalPages,
            'is_admin' => $this->auth->check(),
            'canonical_url' => $canonical,
        ]);

        return Response::cached($html, 600, true);
    }

    private function baseUrl(): string
    {
        return getenv('BASE_URL') ?: 'http://localhost:8000';
    }
}
