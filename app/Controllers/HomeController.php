<?php

namespace App\Controllers;

use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use Twig\Environment;

class HomeController extends BaseController
{
    private CuratedLinkRepository $curatedLinks;

    public function __construct(Environment $view, CuratedLinkRepository $curatedLinks)
    {
        parent::__construct($view);
        $this->curatedLinks = $curatedLinks;
    }

    public function __invoke(): Response
    {
        try {
            $links = $this->curatedLinks->latestPublished(10);
        } catch (\Throwable $exception) {
            $links = [];
        }

        return $this->render('home.twig', [
            'links' => $links,
        ]);
    }
}
