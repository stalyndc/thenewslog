<?php

namespace App\Controllers\Admin;

use App\Helpers\Url;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\FeedFetcher;
use Twig\Environment;

class FeedController extends AdminController
{
    private FeedRepository $feeds;

    private FeedFetcher $fetcher;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, FeedRepository $feeds, ItemRepository $items, FeedFetcher $fetcher)
    {
        parent::__construct($view, $auth, $csrf, $items, $feeds);
        $this->feeds = $feeds;
        $this->fetcher = $fetcher;
    }

    public function index(Request $request): Response
    {
        $context = $this->buildContext($request);

        $flash = $request->query('flash');
        $error = $request->query('error');

        if ($flash) {
            $context['message'] = match ($flash) {
                'created' => 'Feed added successfully.',
                'updated' => 'Feed updated successfully.',
                'deleted' => 'Feed removed successfully.',
                default => null,
            };
        }

        if ($error) {
            $context['error'] = match ($error) {
                'missing' => 'Feed not found.',
                default => null,
            };
        }

        return $this->render('admin/feeds.twig', $this->withAdminMetrics($context));
    }

    public function store(Request $request): Response
    {
        $guard = $this->guardCsrf($request);

        if ($guard !== null) {
            return $guard;
        }

        $title = trim((string) $request->input('title'));
        $feedUrl = trim((string) $request->input('feed_url'));
        $siteUrl = trim((string) $request->input('site_url'));
        $active = $request->input('active') === '1';

        if ($title === '' || $feedUrl === '') {
            $context = $this->buildContext($request, [
                'error' => 'Title and feed URL are required.',
                'create_values' => [
                    'title' => $title,
                    'feed_url' => $feedUrl,
                    'site_url' => $siteUrl,
                    'active' => $active ? 1 : 0,
                ],
            ]);

            return $this->render('admin/feeds.twig', $this->withAdminMetrics($context), 422);
        }

        if (!Url::isValid($feedUrl)) {
            $context = $this->buildContext($request, [
                'error' => 'Enter a valid feed URL using http or https.',
                'create_values' => [
                    'title' => $title,
                    'feed_url' => $feedUrl,
                    'site_url' => $siteUrl,
                    'active' => $active ? 1 : 0,
                ],
            ]);

            return $this->render('admin/feeds.twig', $this->withAdminMetrics($context), 422);
        }

        if ($siteUrl !== '' && !Url::isValid($siteUrl)) {
            $context = $this->buildContext($request, [
                'error' => 'Enter a valid site URL using http or https.',
                'create_values' => [
                    'title' => $title,
                    'feed_url' => $feedUrl,
                    'site_url' => $siteUrl,
                    'active' => $active ? 1 : 0,
                ],
            ]);

            return $this->render('admin/feeds.twig', $this->withAdminMetrics($context), 422);
        }

        $normalizedFeedUrl = Url::normalize($feedUrl);
        $normalizedSiteUrl = $siteUrl === '' ? $normalizedFeedUrl : Url::normalize($siteUrl);

        $this->feeds->ensure([
            'title' => $title,
            'feed_url' => $normalizedFeedUrl,
            'site_url' => $normalizedSiteUrl,
            'active' => $active ? 1 : 0,
        ]);

        return Response::redirect('/admin/feeds?flash=created');
    }

    public function update(Request $request, int $id): Response
    {
        $guard = $this->guardCsrf($request);

        if ($guard !== null) {
            return $guard;
        }

        $feed = $this->feeds->find($id);

        if ($feed === null) {
            return Response::redirect('/admin/feeds?error=missing');
        }

        $title = trim((string) $request->input('title'));
        $feedUrl = trim((string) $request->input('feed_url'));
        $siteUrl = trim((string) $request->input('site_url'));
        $active = $request->input('active') === '1';

        if ($title === '' || $feedUrl === '') {
            $context = $this->buildContext($request, [
                'error' => 'Title and feed URL are required.',
                'edit_feed' => [
                    'id' => $id,
                    'title' => $title,
                    'feed_url' => $feedUrl,
                    'site_url' => $siteUrl,
                    'active' => $active ? 1 : 0,
                ],
            ]);

            return $this->render('admin/feeds.twig', $this->withAdminMetrics($context), 422);
        }

        if (!Url::isValid($feedUrl)) {
            $context = $this->buildContext($request, [
                'error' => 'Enter a valid feed URL using http or https.',
                'edit_feed' => [
                    'id' => $id,
                    'title' => $title,
                    'feed_url' => $feedUrl,
                    'site_url' => $siteUrl,
                    'active' => $active ? 1 : 0,
                ],
            ]);

            return $this->render('admin/feeds.twig', $this->withAdminMetrics($context), 422);
        }

        if ($siteUrl !== '' && !Url::isValid($siteUrl)) {
            $context = $this->buildContext($request, [
                'error' => 'Enter a valid site URL using http or https.',
                'edit_feed' => [
                    'id' => $id,
                    'title' => $title,
                    'feed_url' => $feedUrl,
                    'site_url' => $siteUrl,
                    'active' => $active ? 1 : 0,
                ],
            ]);

            return $this->render('admin/feeds.twig', $this->withAdminMetrics($context), 422);
        }

        $normalizedFeedUrl = Url::normalize($feedUrl);
        $normalizedSiteUrl = $siteUrl === '' ? $normalizedFeedUrl : Url::normalize($siteUrl);

        $this->feeds->update($id, [
            'title' => $title,
            'feed_url' => $normalizedFeedUrl,
            'site_url' => $normalizedSiteUrl,
            'active' => $active ? 1 : 0,
        ]);

        return Response::redirect('/admin/feeds?flash=updated');
    }

    public function destroy(Request $request, int $id): Response
    {
        $guard = $this->guardCsrf($request);

        if ($guard !== null) {
            return $guard;
        }

        $feed = $this->feeds->find($id);

        if ($feed === null) {
            return Response::redirect('/admin/feeds?error=missing');
        }

        $this->feeds->delete($id);

        return Response::redirect('/admin/feeds?flash=deleted');
    }

    public function refresh(Request $request): Response
    {
        $guard = $this->guardCsrf($request);

        if ($guard !== null) {
            return $guard;
        }

        try {
            $this->fetcher->fetch();

            return Response::redirect('/admin/inbox?flash=fetched');
        } catch (\Throwable $exception) {
            return Response::redirect('/admin/inbox?error=fetch_failed');
        }
    }

    private function buildContext(Request $request, array $overrides = []): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        try {
            $feeds = $this->feeds->all($page, $perPage);
        } catch (\Throwable $exception) {
            $feeds = [];
        }

        $total = $this->feeds->countAll();
        $totalPages = max(1, (int) ceil(max(1, $total) / $perPage));

        $editFeed = $overrides['edit_feed'] ?? null;

        if ($editFeed === null) {
            $editId = $request->query('edit');
            if ($editId !== null && $editId !== '') {
                $editFeed = $this->feeds->find((int) $editId);
            }
        }

        $context = [
            'feeds' => $feeds,
            'page' => $page,
            'total_pages' => $totalPages,
            'edit_feed' => $editFeed,
            'create_values' => $overrides['create_values'] ?? [
                'title' => '',
                'feed_url' => '',
                'site_url' => '',
                'active' => 1,
            ],
        ];

        if (isset($overrides['message'])) {
            $context['message'] = $overrides['message'];
        }

        if (isset($overrides['error'])) {
            $context['error'] = $overrides['error'];
        }

        return $context;
    }
}
