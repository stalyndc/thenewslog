<?php

namespace App\Controllers;

use App\Http\Response;

class HomeController extends BaseController
{
    public function __invoke(): Response
    {
        return $this->render('home.twig');
    }
}
