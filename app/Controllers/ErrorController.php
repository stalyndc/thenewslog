<?php

namespace App\Controllers;

use App\Http\Response;

class ErrorController extends BaseController
{
    public function notFound(): Response
    {
        return $this->render('errors/404.twig', [], 404);
    }
}
