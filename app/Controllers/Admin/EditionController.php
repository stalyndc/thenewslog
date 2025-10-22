<?php

namespace App\Controllers\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use App\Repositories\EditionRepository;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use App\Services\Csrf;
use Twig\Environment;

class EditionController extends AdminController
{
    private CuratedLinkRepository $curatedLinks;

    private EditionRepository $editions;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, CuratedLinkRepository $curatedLinks, EditionRepository $editions, ItemRepository $items, FeedRepository $feeds)
    {
        parent::__construct($view, $auth, $csrf, $items, $feeds);
        $this->curatedLinks = $curatedLinks;
        $this->editions = $editions;
    }

    public function show(Request $request, string $date): Response
    {
        if ($request->method() === 'POST') {
            $guard = $this->guardCsrf($request);

            if ($guard !== null) {
                return $guard;
            }
        }

        $edition = $this->editions->ensureForDate($date);

        if ($request->method() === 'POST') {
            $action = $request->input('action');

            try {
                if ($action === 'reorder') {
                    $positions = $request->input('positions', []);
                    if (is_array($positions)) {
                        $this->curatedLinks->updateEditionPositions((int) $edition['id'], $positions);
                    }

                    return Response::redirect('/admin/edition/' . $edition['edition_date'] . '?flash=order');
                }

                if (is_string($action) && str_starts_with($action, 'pin:')) {
                    [$pinKeyword, $linkPart, $statePart] = array_pad(explode(':', $action, 3), 3, null);
                    $linkId = (int) ($linkPart ?? 0);
                    $shouldPin = ($statePart ?? '1') === '1';

                    if ($linkId > 0) {
                        $this->curatedLinks->setPinned($linkId, $shouldPin);

                        if ($shouldPin) {
                            $this->curatedLinks->moveToTopOfEdition($linkId, (int) $edition['id']);
                        }
                    }

                    return Response::redirect('/admin/edition/' . $edition['edition_date'] . '?flash=' . ($shouldPin ? 'pinned' : 'unpinned'));
                }

                if ($action === 'status') {
                    $status = $request->input('status', 'draft');
                    $this->editions->updateStatus((int) $edition['id'], $status === 'published' ? 'published' : 'draft');

                    return Response::redirect('/admin/edition/' . $edition['edition_date'] . '?flash=' . ($status === 'published' ? 'published' : 'draft'));
                }
            } catch (\Throwable) {
                return Response::redirect('/admin/edition/' . $edition['edition_date'] . '?flash=error');
            }
        }

        try {
            $links = $this->curatedLinks->forEditionDate($edition['edition_date']);
        } catch (\Throwable $exception) {
            $links = [];
        }

        $flash = $request->query('flash');
        $message = match ($flash) {
            'order' => 'Edition order updated.',
            'published' => 'Edition marked as published.',
            'draft' => 'Edition reverted to draft.',
            'pinned' => 'Link pinned and promoted to the top.',
            'unpinned' => 'Link unpinned.',
            default => null,
        };

        $error = $flash === 'error' ? 'Unable to update edition. Please try again.' : null;

        return $this->render('admin/edition.twig', $this->withAdminMetrics([
            'date' => $edition['edition_date'],
            'edition' => $edition,
            'links' => $links,
            'message' => $error ? null : $message,
            'error' => $error,
        ]));
    }
}
