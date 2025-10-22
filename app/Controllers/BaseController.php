<?php

namespace App\Controllers;

use App\Http\Response;
use Psr\Log\LoggerInterface;
use Twig\Environment;

abstract class BaseController
{
    protected Environment $view;

    protected ?LoggerInterface $logger = null;

    public function __construct(Environment $view, ?LoggerInterface $logger = null)
    {
        $this->view = $view;
        $this->logger = $logger;
    }

    protected function render(string $template, array $context = [], int $status = 200): Response
    {
        $html = $this->view->render($template, $context);

        $response = new Response($html, $status);
        $response->setHeader('Content-Type', 'text/html; charset=utf-8');

        return $response;
    }

    protected function errorResponse(string $message, int $status = 400, array $context = []): Response
    {
        $this->log('error', $message, ['status' => $status, 'context' => $context]);

        return $this->render('error.twig', array_merge(['error' => $message], $context), $status);
    }

    protected function notFound(string $message = 'Page not found'): Response
    {
        $this->log('warning', 'Resource not found', ['message' => $message]);

        return $this->render('404.twig', ['message' => $message], 404);
    }

    protected function unauthorized(string $message = 'Unauthorized'): Response
    {
        $this->log('warning', 'Unauthorized access attempt', ['message' => $message]);

        return new Response('', 401);
    }

    protected function serverError(string $message = 'Server error', ?\Throwable $exception = null): Response
    {
        $this->log('error', $message, ['exception' => $exception]);

        return $this->render('500.twig', ['message' => $message], 500);
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->{$level}($message, $context);
    }
}
