<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Url;
use App\Repositories\FeedRepository;
use App\Repositories\ItemRepository;
use App\Services\Feed\ConditionalClient;
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

    private ConditionalClient $client;

    public function __construct(
        FeedRepository $feeds,
        ItemRepository $items,
        FeedIo $feedIo,
        LoggerInterface $logger,
        ConditionalClient $client
    ) {
        $this->feeds = $feeds;
        $this->items = $items;
        $this->feedIo = $feedIo;
        $this->logger = $logger;
        $this->client = $client;
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
            $modifiedSince = $this->resolveModifiedSince($feed['last_modified'] ?? null);
            $this->client->setConditionalHeaders($feed['http_etag'] ?? null);

            $result = $this->feedIo->read($feed['feed_url'], null, $modifiedSince);

            if (!$result->getResponse()->isModified()) {
                $metadata = $this->extractMetadata($feed, $result);
                $this->feeds->touchChecked((int) $feed['id'], $metadata['etag'], $metadata['last_modified']);

                $this->logger->info('Feed not modified', [
                    'feed' => $feed['feed_url'],
                    'etag' => $metadata['etag'],
                    'last_modified' => $metadata['last_modified'],
                ]);

                return;
            }

            $inserted = $this->processFeedResult($feed, $result);

            $metadata = $this->extractMetadata($feed, $result);
            $this->feeds->touchChecked((int) $feed['id'], $metadata['etag'], $metadata['last_modified']);

            $this->logger->info('Feed processed', [
                'feed' => $feed['feed_url'],
                'inserted' => $inserted,
                'etag' => $metadata['etag'],
                'last_modified' => $metadata['last_modified'],
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

    private function resolveModifiedSince(?string $value): ?\DateTime
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTime($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $feed
     *
     * @return array{etag: string|null, last_modified: string|null}
     */
    private function extractMetadata(array $feed, Result $result): array
    {
        $response = $result->getResponse();

        $etag = $this->firstHeader($response->getHeader('ETag'));
        $lastModified = null;

        $responseModified = $response->getLastModified();

        if ($responseModified instanceof \DateTimeInterface) {
            $lastModified = $responseModified->format(\DateTimeInterface::RFC2822);
        } else {
            $lastModified = $this->firstHeader($response->getHeader('Last-Modified'));
        }

        if ($etag === null) {
            $etag = $feed['http_etag'] ?? null;
        }

        if ($lastModified === null) {
            $lastModified = $feed['last_modified'] ?? null;
        }

        return [
            'etag' => $etag,
            'last_modified' => $lastModified,
        ];
    }

    /**
     * @param iterable<int, string> $values
     */
    private function firstHeader(iterable $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
