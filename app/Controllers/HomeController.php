<?php

namespace App\\Controllers;

class HomeController extends BaseController
{
    public function __invoke(): void
    {
        $this->render('home.twig');
    }
}
