<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Response;
use App\Repositories\ItemRepository;
use Twig\Environment;

class InboxController extends BaseController
{
    private ItemRepository $items;

    public function __construct(Environment $view, ItemRepository $items)
    {
        parent::__construct($view);
        $this->items = $items;
    }

    public function index(): Response
    {
        try {
            $inbox = $this->items->inbox(25);
        } catch (\Throwable $exception) {
            $inbox = [];
        }

        return $this->render('admin/inbox.twig', [
            'items' => $inbox,
        ]);
    }
}
