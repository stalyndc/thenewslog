<?php

namespace App\Controllers;

use App\Http\Response;
use Twig\Environment;

class PageController extends BaseController
{
    public function __construct(Environment $view)
    {
        parent::__construct($view);
    }

    public function about(): Response
    {
        return $this->render('about.twig', [
            'current_nav' => 'about',
        ]);
    }

    public function contact(): Response
    {
        return $this->render('contact.twig', [
            'current_nav' => 'contact',
        ]);
    }

    public function privacy(): Response
    {
        return $this->render('privacy.twig', [
            'current_nav' => 'privacy',
        ]);
    }

    public function terms(): Response
    {
        return $this->render('terms.twig', [
            'current_nav' => 'terms',
        ]);
    }
}
