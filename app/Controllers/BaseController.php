<?php

namespace App\Controllers;

use App\Http\Response;
use Twig\Environment;

abstract class BaseController
{
    protected Environment $view;

    public function __construct(Environment $view)
    {
        $this->view = $view;
    }

    protected function render(string $template, array $context = [], int $status = 200): Response
    {
        $html = $this->view->render($template, $context);

        $response = new Response($html, $status);
        $response->setHeader('Content-Type', 'text/html; charset=utf-8');

        return $response;
    }
}
