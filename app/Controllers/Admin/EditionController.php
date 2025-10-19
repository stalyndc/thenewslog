<?php

namespace App\Controllers\Admin;

use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Services\Auth;
use Twig\Environment;

class EditionController extends AdminController
{
    private CuratedLinkRepository $curatedLinks;

    public function __construct(Environment $view, Auth $auth, CuratedLinkRepository $curatedLinks)
    {
        parent::__construct($view, $auth);
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
