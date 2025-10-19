<?php

namespace App\\Controllers\\Admin;

use App\\Controllers\\BaseController;

class InboxController extends BaseController
{
    public function index(): void
    {
        $this->render('admin/inbox.twig');
    }
}
