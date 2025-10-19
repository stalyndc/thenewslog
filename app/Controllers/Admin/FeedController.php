<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Response;

class FeedController extends BaseController
{
    public function index(): Response
    {
        return $this->render('admin/feeds.twig');
    }
}
