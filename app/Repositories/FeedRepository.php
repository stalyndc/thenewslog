<?php

namespace App\Repositories;

class FeedRepository extends BaseRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->fetchAll('SELECT * FROM feeds ORDER BY title ASC');
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

    public function touchChecked(int $id): bool
    {
        $sql = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? 'UPDATE feeds SET last_checked_at = CURRENT_TIMESTAMP WHERE id = :id'
            : 'UPDATE feeds SET last_checked_at = CURRENT_TIMESTAMP WHERE id = :id';

        return $this->execute($sql, ['id' => $id]);
    }
}
