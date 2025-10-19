<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Response;

class EditionController extends BaseController
{
    public function show(string $date): Response
    {
        return $this->render('admin/edition.twig', ['date' => $date]);
    }
}
