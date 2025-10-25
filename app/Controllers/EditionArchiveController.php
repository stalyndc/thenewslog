<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use App\Repositories\TagRepository;
use Twig\Environment;
use App\Services\Auth;

class EditionArchiveController extends BaseController
{
    private EditionRepository $editions;

    private CuratedLinkRepository $curatedLinks;

    private TagRepository $tags;

    private Auth $auth;

    public function __construct(Environment $view, EditionRepository $editions, CuratedLinkRepository $curatedLinks, TagRepository $tags, Auth $auth)
    {
        parent::__construct($view);
        $this->editions = $editions;
        $this->curatedLinks = $curatedLinks;
        $this->tags = $tags;
        $this->auth = $auth;
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 12;

        $editions = $this->editions->publishedWithCounts($page, $perPage);
        $total = $this->editions->countPublished();
        $totalPages = max(1, (int) ceil(max(1, $total) / $perPage));
        $nextPage = $page < $totalPages ? $page + 1 : null;

        return $this->render('editions.twig', [
            'current_nav' => 'editions',
            'editions' => $editions,
            'page' => $page,
            'total_pages' => $totalPages,
            'next_page' => $nextPage,
            'is_admin' => $this->auth->check(),
        ]);
    }

    public function show(Request $request, string $date): Response
    {
        $edition = $this->editions->findPublishedByDate($date);

        if ($edition === null) {
            return new Response('Edition not found', 404);
        }

        $links = $this->curatedLinks->publishedForEditionDate($edition['edition_date'], 50);

        if (empty($links)) {
            return new Response('Edition not found', 404);
        }

        $tags = $this->tags->tagsForCuratedLinks(array_column($links, 'id'));

        return $this->render('edition_show.twig', [
            'current_nav' => 'editions',
            'edition' => $edition,
            'links' => $links,
            'tagsByLink' => $tags,
            'is_admin' => $this->auth->check(),
        ]);
    }

    public function partial(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 12;

        $editions = $this->editions->publishedWithCounts($page, $perPage);

        if (empty($editions)) {
            return new Response('', 204);
        }

        $total = $this->editions->countPublished();
        $totalPages = max(1, (int) ceil(max(1, $total) / $perPage));
        $nextPage = $page < $totalPages ? $page + 1 : null;

        $html = $this->view->render('partials/edition_items.twig', [
            'editions' => $editions,
            'next_page' => $nextPage,
            'total_pages' => $totalPages,
        ]);

        $response = new Response($html);
        $response->setHeader('Content-Type', 'text/html; charset=utf-8');
        $response->setHeader('HX-Trigger', json_encode([
            'editions:page-loaded' => [
                'page' => $page,
                'next' => $nextPage,
                'total_pages' => $totalPages,
            ],
        ]));

        return $response;
    }
}
