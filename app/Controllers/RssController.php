<?php

namespace App\Controllers;

use App\Http\Response;
use App\Repositories\CuratedLinkRepository;
use DateTimeImmutable;
use DateTimeZone;

class RssController
{
    private CuratedLinkRepository $curatedLinks;

    public function __construct(CuratedLinkRepository $curatedLinks)
    {
        $this->curatedLinks = $curatedLinks;
    }

    public function daily(): Response
    {
        $today = date('Y-m-d');
        $links = $this->curatedLinks->publishedForEditionDate($today, 20);

        if (empty($links)) {
            $latestEdition = $this->curatedLinks->latestPublishedEdition();
            if ($latestEdition !== null) {
                $links = $this->curatedLinks->publishedForEditionDate($latestEdition['edition_date'], 20);
                $today = $latestEdition['edition_date'];
            }
        }

        $title = sprintf('The News Log — Daily Edition (%s)', date('D, M j, Y', strtotime($today)));
        // Channel canonical link should reference the edition page
        $link = rtrim($this->baseUrl(), '/') . '/editions/' . rawurlencode($today);

        $xml = $this->buildFeed($title, $link, 'Daily curated selection', $links);

        $response = new Response($xml);
        $response->setHeader('Content-Type', 'application/rss+xml; charset=utf-8');

        return $response;
    }

    /**
     * @param array<int, array<string, mixed>> $links
     */
    private function buildFeed(string $title, string $link, string $description, array $links): string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $itemsXml = [];

        foreach ($links as $linkRow) {
            if (!is_array($linkRow)) {
                continue;
            }

            $id = $linkRow['id'] ?? null;
            $itemTitle = $linkRow['title'] ?? '';
            $itemLink = $linkRow['source_url'] ?? $linkRow['url'] ?? $link;
            $blurb = $linkRow['blurb'] ?? '';

            if ($id === null || $itemTitle === '' || $itemLink === '') {
                continue;
            }

            $guid = $linkRow['url'] ?? ($this->baseUrl() . '/curated/' . $id);
            $pubDate = $this->formatRssDate($linkRow['published_at'] ?? $linkRow['updated_at'] ?? null);

            $itemsXml[] = sprintf(
                "    <item>\n" .
                "      <title>%s</title>\n" .
                "      <link>%s</link>\n" .
                "      <guid>%s</guid>\n" .
                "      <description><![CDATA[%s]]></description>\n" .
                "%s" .
                "    </item>",
                htmlspecialchars($itemTitle, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                htmlspecialchars($itemLink, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                htmlspecialchars($guid, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                htmlspecialchars($blurb, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                $pubDate ? "      <pubDate>{$pubDate}</pubDate>\n" : ''
            );
        }

        return sprintf(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
            "<rss version=\"2.0\">\n" .
            "  <channel>\n" .
            "    <title>%s</title>\n" .
            "    <link>%s</link>\n" .
            "    <description>%s</description>\n" .
            "    <lastBuildDate>%s</lastBuildDate>\n" .
            "%s\n" .
            "  </channel>\n" .
            "</rss>\n",
            htmlspecialchars($title, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
            htmlspecialchars($link, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
            htmlspecialchars($description, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
            $now->format(DATE_RSS),
            implode("\n", $itemsXml)
        );
    }

    private function baseUrl(): string
    {
        return getenv('BASE_URL') ?: 'http://localhost:8000';
    }

    private function formatRssDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return gmdate(DATE_RSS, $timestamp);
    }
}
