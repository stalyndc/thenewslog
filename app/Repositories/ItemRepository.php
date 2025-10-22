<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repository for managing RSS feed items.
 *
 * Handles CRUD operations and querying for feed items ingested from RSS feeds.
 * Items can have statuses: 'new', 'discarded', or 'curated'.
 */
class ItemRepository extends BaseRepository
{
    /**
     * Get paginated inbox items (status='new').
     *
     * @param int $limit Items per page (1-100, default 25)
     * @param int $page Page number (default 1)
     * @param int|null $feedId Optional feed filter
     * @return array<int, array<string, mixed>>
     */
    public function inbox(int $limit = 25, int $page = 1, ?int $feedId = null): array
    {
        $limit = max(1, min(100, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $where = 'WHERE items.status = \'new\'';
        $params = [];

        if ($feedId !== null) {
            $where .= ' AND items.feed_id = :feed_id';
            $params['feed_id'] = $feedId;
        }

        $sql = <<<'SQL'
SELECT items.*, feeds.title AS feed_title
FROM items
JOIN feeds ON feeds.id = items.feed_id
%s
ORDER BY items.published_at IS NULL, items.published_at DESC, items.created_at DESC
LIMIT :limit OFFSET :offset
SQL;

        $sql = sprintf($sql, $where);
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll($sql, $params);
    }


    /**
     * Find a single item by ID with feed details.
     *
     * @return array<string, mixed>|null
     */
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

    /**
     * Find an item by its URL hash (duplicate detection).
     *
     * @return array<string, mixed>|null
     */
    public function findByHash(string $hash): ?array
    {
        return $this->fetch('SELECT * FROM items WHERE url_hash = :hash', ['hash' => $hash]);
    }

    /**
     * Create a new item from feed entry.
     *
     * @param array<string, mixed> $attributes Item data
     * @return int Inserted item ID
     */
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

    /**
     * Update item status with validation.
     *
     * @param string $status One of: 'new', 'discarded', 'curated'
     * @throws \InvalidArgumentException If status is invalid
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['new', 'discarded', 'curated'];

        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s"', $status));
        }

        $sql = 'UPDATE items SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id';

        return $this->execute($sql, [
            'id' => $id,
            'status' => $status,
        ]);
    }

    /**
     * Mark an item as curated (status='curated').
     */
    public function markCurated(int $id): bool
    {
        $sql = 'UPDATE items SET status = \'curated\', updated_at = CURRENT_TIMESTAMP WHERE id = :id';

        return $this->execute($sql, ['id' => $id]);
    }

    /**
     * Count new (unprocessed) items, optionally by feed.
     *
     * @param int|null $feedId Optional filter by feed ID
     */
    public function countNew(?int $feedId = null): int
    {
        if ($feedId === null) {
            return $this->countByStatus('new');
        }

        $row = $this->fetch('SELECT COUNT(*) AS aggregate FROM items WHERE status = :status AND feed_id = :feed_id', [
            'status' => 'new',
            'feed_id' => $feedId,
        ]);

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * Count items by status.
     *
     * @param string $status Item status ('new', 'discarded', 'curated')
     */
    public function countByStatus(string $status): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS aggregate FROM items WHERE status = :status', ['status' => $status]);

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * Delete an item by ID (cascade deletes related curated_links).
     */
    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM items WHERE id = :id', ['id' => $id]);
    }
}
