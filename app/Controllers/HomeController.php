<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\TagRepository;
use App\Services\Auth;
use Twig\Environment;

class HomeController extends BaseController
{
    private CuratedLinkRepository $curatedLinks;

    private TagRepository $tags;

    private Auth $auth;

    public function __construct(Environment $view, CuratedLinkRepository $curatedLinks, TagRepository $tags, Auth $auth)
    {
        parent::__construct($view);
        $this->curatedLinks = $curatedLinks;
        $this->tags = $tags;
        $this->auth = $auth;
    }

    public function __invoke(Request $request): Response
    {
        $dateParam = $request->query('date');
        $requestedDate = $this->parseEditionDate($dateParam);

        $editionDate = $requestedDate ?? date('Y-m-d');
        $links = $this->curatedLinks->publishedForEditionDate($editionDate, 12);

        if (empty($links)) {
            $latestEdition = $this->curatedLinks->latestPublishedEdition();
            if ($latestEdition !== null) {
                $editionDate = $latestEdition['edition_date'];
                $links = $this->curatedLinks->publishedForEditionDate($editionDate, 12);
            }
        }

        $editionDisplay = $editionDate ? date('D, M j, Y', strtotime($editionDate)) : null;

        $tags = $this->tags->tagsForCuratedLinks(array_column($links, 'id'));

        $canonical = rtrim($this->baseUrl(), '/') . '/';

        $count = is_array($links) ? count($links) : 0;
        $meta = $count > 0
            ? sprintf('Daily edition for %s — %d curated links.', $editionDisplay ?? $editionDate, $count)
            : 'Daily curated links in tech, startups, AI and more.';

        $html = $this->view->render('home.twig', [
            'links' => $links,
            'edition_date' => $editionDate,
            'edition_display' => $editionDisplay,
            'tagsByLink' => $tags,
            'is_admin' => $this->auth->check(),
            'canonical_url' => $canonical,
            'meta_description' => $meta,
        ]);

        return Response::cached($html, 60, true);
    }

    private function parseEditionDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function baseUrl(): string
    {
        return getenv('BASE_URL') ?: 'http://localhost:8000';
    }
}
