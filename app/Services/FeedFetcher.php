<?php

namespace App\Services;

use App\Helpers\Url;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use FeedIo\FeedIo;
use FeedIo\Reader\ReadErrorException;
use FeedIo\Reader\Result; 
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
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
        $feeds = $this->feeds->active();

        foreach ($feeds as $feed) {
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
            $this->processFeedResult($feed, $result);
            $this->feeds->touchChecked((int) $feed['id']);
        } catch (ReadErrorException $exception) {
            $this->logger->error('Failed to read feed', [
                'feed' => $feed['feed_url'],
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Unexpected error while fetching feed', [
                'feed' => $feed['feed_url'],
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function processFeedResult(array $feed, Result $result): void
    {
        $resource = $result->getFeed();

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

            $publishedAt = $item->getLastModified() ?: $item->getLastModifiedSince();

            $this->items->create([
                'feed_id' => $feed['id'],
                'title' => $item->getTitle() ?: $link,
                'url' => $normalizedUrl,
                'url_hash' => $hash,
                'summary_raw' => $item->getDescription(),
                'author' => $item->getAuthor()?->getName(),
                'published_at' => $publishedAt ? $publishedAt->format('Y-m-d H:i:s') : null,
                'source_name' => $resource->getTitle() ?: $feed['title'],
                'status' => 'new',
            ]);
        }
    }

    public static function buildFeedIo(ClientInterface $client = null): FeedIo
    {
        $client ??= new Client([
            'timeout' => 10,
            'allow_redirects' => true,
        ]);

        $logger = new \Psr\Log\NullLogger();
        $httpClient = new \FeedIo\Adapter\Guzzle\Client($client);
        $adapter = new \FeedIo\Adapter\Guzzle\Client($client);

        $feedIo = new FeedIo($adapter, $logger);
        $feedIo->getPsr18Client()->setClient($httpClient);

        return $feedIo;
    }
}
