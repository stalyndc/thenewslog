<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class FeedController extends BaseController
{
    public function index(): void
    {
        $this->render('admin/feeds.twig');
    }
}
