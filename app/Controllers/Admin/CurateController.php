<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\ItemRepository;
use Twig\Environment;

class CurateController extends BaseController
{
    private ItemRepository $items;

    private CuratedLinkRepository $curatedLinks;

    public function __construct(Environment $view, ItemRepository $items, CuratedLinkRepository $curatedLinks)
    {
        parent::__construct($view);
        $this->items = $items;
        $this->curatedLinks = $curatedLinks;
    }

    public function show(int $id): Response
    {
        try {
            $item = $this->items->find($id);
        } catch (\Throwable $exception) {
            $item = null;
        }

        return $this->render('admin/curate.twig', [
            'item' => $item,
            'curated' => $this->resolveCuratedFromItem($item),
        ]);
    }

    public function store(int $id): Response
    {
        try {
            $item = $this->items->find($id);
        } catch (\Throwable $exception) {
            $item = null;
        }

        return $this->render('admin/curate.twig', [
            'item' => $item,
            'curated' => $this->resolveCuratedFromItem($item),
            'message' => 'Curated link submission is pending implementation.',
        ]);
    }

    private function resolveCuratedFromItem(?array $item): ?array
    {
        if ($item === null) {
            return null;
        }

        try {
            return $this->curatedLinks->findByItem($item['id']);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
