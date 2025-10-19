<?php

namespace App\\Controllers;

use Twig\\Environment;

abstract class BaseController
{
    protected Environment $view;

    public function __construct(Environment $view)
    {
        $this->view = $view;
    }

    protected function render(string $template, array $context = []): void
    {
        echo $this->view->render($template, $context);
    }
}
