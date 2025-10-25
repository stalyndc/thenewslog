<?php

namespace App\Controllers;

use App\Http\Response;
use Twig\Environment;
use App\Services\Auth;

class PageController extends BaseController
{
    private Auth $auth;

    public function __construct(Environment $view, Auth $auth)
    {
        parent::__construct($view);
        $this->auth = $auth;
    }

    public function about(): Response
    {
        return $this->render('about.twig', [
            'current_nav' => 'about',
            'is_admin' => $this->auth->check(),
        ]);
    }

    public function privacy(): Response
    {
        return $this->render('privacy.twig', [
            'current_nav' => 'privacy',
            'is_admin' => $this->auth->check(),
        ]);
    }

    public function terms(): Response
    {
        return $this->render('terms.twig', [
            'current_nav' => 'terms',
            'is_admin' => $this->auth->check(),
        ]);
    }
}
