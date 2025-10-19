<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Response;

class CurateController extends BaseController
{
    public function show(int $id): Response
    {
        return $this->render('admin/curate.twig', ['itemId' => $id]);
    }

    public function store(int $id): Response
    {
        return $this->render('admin/curate.twig', [
            'itemId' => $id,
            'message' => 'Curated link submission is pending implementation.',
        ]);
    }
}
