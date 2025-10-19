<?php

namespace App\Repositories;

class ItemRepository extends BaseRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function inbox(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $sql = <<<'SQL'
SELECT items.*, feeds.title AS feed_title
FROM items
JOIN feeds ON feeds.id = items.feed_id
WHERE items.status = 'new'
ORDER BY items.published_at IS NULL, items.published_at DESC, items.created_at DESC
LIMIT %d
SQL;

        $sql = sprintf($sql, $limit);

        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        $sql = <<<'SQL'
SELECT items.*, feeds.title AS feed_title, feeds.site_url
FROM items
JOIN feeds ON feeds.id = items.feed_id
WHERE items.id = :id
SQL;

        return $this->fetch($sql, ['id' => $id]);
    }

    public function findByHash(string $hash): ?array
    {
        return $this->fetch('SELECT * FROM items WHERE url_hash = :hash', ['hash' => $hash]);
    }

    public function create(array $attributes): int
    {
        $sql = <<<'SQL'
INSERT INTO items (
    feed_id, title, url, url_hash, summary_raw,
    author, published_at, source_name, status
) VALUES (
    :feed_id, :title, :url, :url_hash, :summary_raw,
    :author, :published_at, :source_name, :status
)
SQL;

        return $this->insert($sql, [
            'feed_id' => $attributes['feed_id'],
            'title' => $attributes['title'],
            'url' => $attributes['url'],
            'url_hash' => $attributes['url_hash'],
            'summary_raw' => $attributes['summary_raw'] ?? null,
            'author' => $attributes['author'] ?? null,
            'published_at' => $attributes['published_at'] ?? null,
            'source_name' => $attributes['source_name'] ?? null,
            'status' => $attributes['status'] ?? 'new',
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['new', 'ignored', 'curated'];

        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s"', $status));
        }

        $sql = 'UPDATE items SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id';

        return $this->execute($sql, [
            'id' => $id,
            'status' => $status,
        ]);
    }

    public function markCurated(int $id): bool
    {
        $sql = 'UPDATE items SET status = \'curated\', curated_at = CURRENT_TIMESTAMP WHERE id = :id';

        return $this->execute($sql, ['id' => $id]);
    }

    public function countByStatus(string $status): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS aggregate FROM items WHERE status = :status', ['status' => $status]);

        return (int) ($row['aggregate'] ?? 0);
    }

    public function countNew(): int
    {
        return $this->countByStatus('new');
    }
}
