<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\TagRepository;
use Twig\Environment;

class StreamController extends BaseController
{
    private CuratedLinkRepository $curatedLinks;

    private TagRepository $tags;

    public function __construct(Environment $view, CuratedLinkRepository $curatedLinks, TagRepository $tags)
    {
        parent::__construct($view);
        $this->curatedLinks = $curatedLinks;
        $this->tags = $tags;
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
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function deriveLastUpdated(array $items): ?string
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
        $diffMinutes = max(0, (int) floor((time() - $latest) / 60));

        return $diffMinutes === 0 ? 'Just now' : sprintf('%d min ago', $diffMinutes);
    }
}
