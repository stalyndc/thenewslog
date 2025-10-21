<?php

declare(strict_types=1);

namespace App\Services\Feed;

use FeedIo\Adapter\Guzzle\Client as FeedIoClient;

class ConditionalClient extends FeedIoClient
{
    /**
     * @var array<string, string>
     */
    private array $extraHeaders = [];

    public function setConditionalHeaders(?string $etag): void
    {
        $headers = [];

        if ($etag !== null && $etag !== '') {
            $headers['If-None-Match'] = $etag;
        }

        $this->extraHeaders = $headers;
    }

    protected function getOptions(\DateTime $modifiedSince): array
    {
        $options = parent::getOptions($modifiedSince);

        if (!empty($this->extraHeaders)) {
            $options['headers'] = array_merge($options['headers'], $this->extraHeaders);
            $this->extraHeaders = [];
        }

        return $options;
    }
}
