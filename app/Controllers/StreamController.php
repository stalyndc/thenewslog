<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use Twig\Environment;

class StreamController extends BaseController
{
    private CuratedLinkRepository $curatedLinks;

    public function __construct(Environment $view, CuratedLinkRepository $curatedLinks)
    {
        parent::__construct($view);
        $this->curatedLinks = $curatedLinks;
    }

    public function __invoke(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $items = $this->curatedLinks->stream($page, $perPage);
        $total = $this->curatedLinks->countStream();
        $totalPages = max(1, (int) ceil($total / $perPage));

        return $this->render('stream.twig', [
            'current_nav' => 'stream',
            'items' => $items,
            'page' => $page,
            'total_pages' => $totalPages,
        ]);
    }
}
