<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Url;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use FeedIo\FeedIo;
use FeedIo\Feed\ItemInterface;
use FeedIo\Reader\ReadErrorException;
use FeedIo\Reader\Result;
use Psr\Log\LoggerInterface;

class FeedFetcher
{
    private FeedRepository $feeds;

    private ItemRepository $items;

    private FeedIo $feedIo;

    private LoggerInterface $logger;

    public function __construct(
        FeedRepository $feeds,
        ItemRepository $items,
        FeedIo $feedIo,
        LoggerInterface $logger
    ) {
        $this->feeds = $feeds;
        $this->items = $items;
        $this->feedIo = $feedIo;
        $this->logger = $logger;
    }

    public function fetch(): void
    {
        foreach ($this->feeds->active() as $feed) {
            $this->fetchFeed($feed);
        }
    }

    /**
     * @param array<string, mixed> $feed
     */
    private function fetchFeed(array $feed): void
    {
        try {
            $result = $this->feedIo->read($feed['feed_url']);
            $inserted = $this->processFeedResult($feed, $result);
            $this->feeds->touchChecked((int) $feed['id']);

            $this->logger->info('Feed processed', [
                'feed' => $feed['feed_url'],
                'inserted' => $inserted,
            ]);
        } catch (ReadErrorException $exception) {
            $this->feeds->incrementFailCount((int) $feed['id']);
            $this->logger->error('Failed to read feed', [
                'feed' => $feed['feed_url'],
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $this->feeds->incrementFailCount((int) $feed['id']);
            $this->logger->error('Unexpected error while fetching feed', [
                'feed' => $feed['feed_url'],
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function processFeedResult(array $feed, Result $result): int
    {
        $resource = $result->getFeed();
        $inserted = 0;

        /** @var ItemInterface $item */
        foreach ($resource as $item) {
            $link = $item->getLink();

            if ($link === null) {
                continue;
            }

            $normalizedUrl = Url::normalize($link);
            $hash = sha1($normalizedUrl);

            if ($this->items->findByHash($hash) !== null) {
                continue;
            }

            $publishedAt = $item->getLastModified() ?: $item->getPublishedDate();

            $this->items->create([
                'feed_id' => (int) $feed['id'],
                'title' => $item->getTitle() ?: $link,
                'url' => $normalizedUrl,
                'url_hash' => $hash,
                'summary_raw' => $item->getContent() ?: null,
                'author' => $item->getAuthor()?->getName(),
                'published_at' => $publishedAt ? $publishedAt->format('Y-m-d H:i:s') : null,
                'source_name' => $resource->getTitle() ?: $feed['title'],
                'status' => 'new',
            ]);

            $inserted++;
        }

        return $inserted;
    }
}
