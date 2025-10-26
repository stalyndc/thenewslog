<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use PDO;

class HealthController
{
    private PDO $db;
    private FeedRepository $feeds;
    private ItemRepository $items;

    public function __construct(PDO $db, FeedRepository $feeds, ItemRepository $items)
    {
        $this->db = $db;
        $this->feeds = $feeds;
        $this->items = $items;
    }

    public function __invoke(Request $request): Response
    {
        $deep = $request->query('deep') === '1' || str_contains($request->path(), '/healthz/deep');

        $payload = [
            'status' => 'ok',
            'time' => gmdate('c'),
        ];

        $statusCode = 200;

        if ($deep) {
            try {
                $this->db->query('SELECT 1');
                $payload['db'] = 'ok';
            } catch (\Throwable $e) {
                $payload['db'] = 'error';
                $payload['status'] = 'error';
                $statusCode = 503;
            }

            try {
                $payload['feeds_total'] = $this->feeds->countAll();
            } catch (\Throwable) {
                $payload['feeds_total'] = null;
                $payload['status'] = 'error';
                $statusCode = 503;
            }

            try {
                $payload['inbox_new'] = $this->items->countNew();
            } catch (\Throwable) {
                $payload['inbox_new'] = null;
                $payload['status'] = 'error';
                $statusCode = 503;
            }
        }

        $response = Response::json($payload, $statusCode);
        // Explicitly disable caching
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
