<?php

namespace App\Repositories;

use App\Helpers\Url;

class FeedRepository extends BaseRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(int $page = 1, int $perPage = 25): array
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $statement = $this->connection->prepare('SELECT * FROM feeds ORDER BY title ASC LIMIT :limit OFFSET :offset');
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function active(): array
    {
        return $this->fetchAll('SELECT * FROM feeds WHERE active = 1 ORDER BY title ASC');
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM feeds WHERE id = :id', ['id' => $id]);
    }

    public function findByFeedUrl(string $feedUrl): ?array
    {
        return $this->fetch('SELECT * FROM feeds WHERE feed_url = :feed_url', ['feed_url' => $feedUrl]);
    }

    public function create(array $attributes): int
    {
        $sql = <<<'SQL'
INSERT INTO feeds (title, site_url, feed_url, active)
VALUES (:title, :site_url, :feed_url, :active)
SQL;

        return $this->insert($sql, [
            'title' => $attributes['title'] ?? '',
            'site_url' => $attributes['site_url'] ?? '',
            'feed_url' => $attributes['feed_url'] ?? '',
            'active' => (int) ($attributes['active'] ?? 1),
        ]);
    }

    public function update(int $id, array $attributes): bool
    {
        $sql = <<<'SQL'
UPDATE feeds
SET title = :title,
    site_url = :site_url,
    feed_url = :feed_url,
    active = :active,
    updated_at = CURRENT_TIMESTAMP
WHERE id = :id
SQL;

        return $this->execute($sql, [
            'id' => $id,
            'title' => $attributes['title'] ?? '',
            'site_url' => $attributes['site_url'] ?? '',
            'feed_url' => $attributes['feed_url'] ?? '',
            'active' => (int) ($attributes['active'] ?? 1),
        ]);
    }

    public function ensure(array $attributes): array
    {
        $feedUrl = Url::normalize($attributes['feed_url'] ?? '');
        $existing = $this->findByFeedUrl($feedUrl);

        $payload = [
            'title' => $attributes['title'] ?? $feedUrl,
            'site_url' => $attributes['site_url'] ?? $this->inferSiteUrl($feedUrl),
            'feed_url' => $feedUrl,
            'active' => (int) ($attributes['active'] ?? 1),
        ];

        if ($existing !== null) {
            $this->update((int) $existing['id'], $payload);

            return $this->find((int) $existing['id']) ?? $payload;
        }

        $id = $this->create($payload);

        return $this->find($id) ?? $payload;
    }

    public function touchChecked(int $id): bool
    {
        return $this->execute('UPDATE feeds SET last_checked_at = CURRENT_TIMESTAMP, fail_count = 0 WHERE id = :id', ['id' => $id]);
    }

    public function incrementFailCount(int $id): void
    {
        $this->execute('UPDATE feeds SET fail_count = fail_count + 1 WHERE id = :id', ['id' => $id]);
    }

    public function resetFailCount(int $id): void
    {
        $this->execute('UPDATE feeds SET fail_count = 0 WHERE id = :id', ['id' => $id]);
    }

    private function inferSiteUrl(string $feedUrl): string
    {
        $parts = parse_url($feedUrl);

        if ($parts === false || empty($parts['host'])) {
            return $feedUrl;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];

        return sprintf('%s://%s', $scheme, $host);
    }

    public function latestFetchTime(): ?string
    {
        $row = $this->fetch('SELECT MAX(last_checked_at) AS last_checked FROM feeds');

        return $row['last_checked'] ?? null;
    }

    public function failingCount(): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS aggregate FROM feeds WHERE fail_count >= 3');

        return (int) ($row['aggregate'] ?? 0);
    }

    public function countAll(): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS aggregate FROM feeds');

        return (int) ($row['aggregate'] ?? 0);
    }
}
