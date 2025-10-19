<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Auth;
use Twig\Environment;

abstract class AdminController extends BaseController
{
    protected Auth $auth;

    public function __construct(Environment $view, Auth $auth)
    {
        parent::__construct($view);
        $this->auth = $auth;

        if (!$this->auth->check()) {
            header('Location: /admin');
            exit;
        }
    }
}
