<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use Twig\Environment;

abstract class AdminController extends BaseController
{
    protected Auth $auth;

    private ?ItemRepository $items;

    private ?FeedRepository $feeds;

    public function __construct(Environment $view, Auth $auth, ?ItemRepository $items = null, ?FeedRepository $feeds = null)
    {
        parent::__construct($view);
        $this->auth = $auth;
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

    private function formatRelative(string $timestamp): string
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
}
