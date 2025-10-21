<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\TagRepository;
use App\Services\Auth;
use Twig\Environment;

class StreamController extends BaseController
{
    private CuratedLinkRepository $curatedLinks;

    private TagRepository $tags;

    private Auth $auth;

    public function __construct(Environment $view, CuratedLinkRepository $curatedLinks, TagRepository $tags, Auth $auth)
    {
        parent::__construct($view);
        $this->curatedLinks = $curatedLinks;
        $this->tags = $tags;
        $this->auth = $auth;
    }

    public function __invoke(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $items = $this->curatedLinks->stream($page, $perPage);
        $linkIds = array_column($items, 'id');
        $tags = $this->tags->tagsForCuratedLinks($linkIds);
        $total = $this->curatedLinks->countStream();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $lastUpdated = $this->deriveLastUpdated($items);

        return $this->render('stream.twig', [
            'current_nav' => 'stream',
            'items' => $items,
            'page' => $page,
            'total_pages' => $totalPages,
            'tagsByLink' => $tags,
            'last_updated' => $lastUpdated,
            'is_admin' => $this->auth->check(),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function deriveLastUpdated(array $items): ?array
    {
        $timestamps = [];

        foreach ($items as $item) {
            if (!empty($item['published_at'])) {
                $timestamps[] = strtotime($item['published_at']);
            } elseif (!empty($item['updated_at'])) {
                $timestamps[] = strtotime($item['updated_at']);
            }
        }

        $timestamps = array_filter($timestamps, static fn ($value) => $value !== false);

        if (empty($timestamps)) {
            return null;
        }

        $latest = max($timestamps);
        $diffSeconds = max(0, time() - $latest);
        $iso = gmdate('c', $latest);

        $relative = match (true) {
            $diffSeconds < 60 => 'Just now',
            $diffSeconds < 3600 => sprintf('%d min ago', (int) floor($diffSeconds / 60)),
            $diffSeconds < 86400 => sprintf('%d hr%s ago', (int) floor($diffSeconds / 3600), ((int) floor($diffSeconds / 3600)) === 1 ? '' : 's'),
            default => sprintf('%d day%s ago', (int) floor($diffSeconds / 86400), ((int) floor($diffSeconds / 86400)) === 1 ? '' : 's'),
        };

        return [
            'relative' => $relative,
            'iso' => $iso,
        ];
    }
}
