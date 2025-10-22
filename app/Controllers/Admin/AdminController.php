<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use App\Services\Csrf;
use Psr\Log\LoggerInterface;
use Twig\Environment;

abstract class AdminController extends BaseController
{
    protected Auth $auth;

    protected Csrf $csrf;

    private ?ItemRepository $items;

    private ?FeedRepository $feeds;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, ?ItemRepository $items = null, ?FeedRepository $feeds = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($view, $logger);
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->items = $items;
        $this->feeds = $feeds;

        if (!$this->auth->check()) {
            header('Location: /admin');
            exit;
        }
    }

    protected function withAdminMetrics(array $context = []): array
    {
        $metrics = null;

        if ($this->items !== null || $this->feeds !== null) {
            $inboxCount = $this->items ? $this->items->countNew() : null;
            $lastFetch = $this->feeds ? $this->feeds->latestFetchTime() : null;
            $failing = $this->feeds ? $this->feeds->failingCount() : null;

            $metrics = [
                'inbox_count' => $inboxCount,
                'last_fetch' => $lastFetch ? $this->formatRelative($lastFetch) : null,
                'failing_feeds' => $failing,
            ];
        }

        if ($metrics !== null) {
            $context['admin_metrics'] = $metrics;
        }

        return $context;
    }

    protected function formatRelative(string $timestamp): string
    {
        $time = strtotime($timestamp);

        if ($time === false) {
            return $timestamp;
        }

        $diff = time() - $time;

        if ($diff < 60) {
            return 'just now';
        }

        $minutes = (int) floor($diff / 60);

        if ($minutes < 60) {
            return sprintf('%d min ago', $minutes);
        }

        $hours = (int) floor($minutes / 60);

        if ($hours < 24) {
            return sprintf('%d hr%s ago', $hours, $hours === 1 ? '' : 's');
        }

        $days = (int) floor($hours / 24);

        return sprintf('%d day%s ago', $days, $days === 1 ? '' : 's');
    }

    protected function guardCsrf(Request $request): ?Response
    {
        $token = $this->csrf->extractToken($request);

        if ($this->csrf->validate($token)) {
            return null;
        }

        return new Response('', 419);
    }
}
