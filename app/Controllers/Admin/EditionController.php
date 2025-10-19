<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use Twig\Environment;

class EditionController extends BaseController
{
    private CuratedLinkRepository $curatedLinks;

    public function __construct(Environment $view, CuratedLinkRepository $curatedLinks)
    {
        parent::__construct($view);
        $this->curatedLinks = $curatedLinks;
    }

    public function show(string $date): Response
    {
        try {
            $links = $this->curatedLinks->forEditionDate($date);
        } catch (\Throwable $exception) {
            $links = [];
        }

        return $this->render('admin/edition.twig', [
            'date' => $date,
            'links' => $links,
        ]);
    }
}
