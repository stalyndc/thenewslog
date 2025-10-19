<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class CurateController extends BaseController
{
    public function show(int $id): void
    {
        $this->render('admin/curate.twig', ['itemId' => $id]);
    }

    public function store(int $id): void
    {
        $this->render('admin/curate.twig', [
            'itemId' => $id,
            'message' => 'Curated link submission is pending implementation.',
        ]);
    }
}
