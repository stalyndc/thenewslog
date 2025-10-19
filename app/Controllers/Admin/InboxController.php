<?php

namespace App\Controllers\Admin;

use App\Http\Response;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use Twig\Environment;

class InboxController extends AdminController
{
    private ItemRepository $items;

    public function __construct(Environment $view, Auth $auth, ItemRepository $items)
    {
        parent::__construct($view, $auth);
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
