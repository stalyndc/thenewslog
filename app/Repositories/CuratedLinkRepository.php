<?php

namespace App\Repositories;

class CuratedLinkRepository extends BaseRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestPublished(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        $sql = <<<'SQL'
SELECT cl.*, e.edition_date
FROM curated_links cl
LEFT JOIN edition_curated_link ecl ON ecl.curated_link_id = cl.id
LEFT JOIN editions e ON e.id = ecl.edition_id
WHERE cl.published_at IS NOT NULL
ORDER BY cl.published_at DESC, cl.id DESC
LIMIT %d
SQL;

        $sql = sprintf($sql, $limit);

        return $this->fetchAll($sql);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forEditionDate(string $date): array
    {
        $sql = <<<'SQL'
SELECT cl.*, ecl.position
FROM editions e
JOIN edition_curated_link ecl ON ecl.edition_id = e.id
JOIN curated_links cl ON cl.id = ecl.curated_link_id
WHERE e.edition_date = :edition_date
ORDER BY ecl.position ASC
SQL;

        return $this->fetchAll($sql, ['edition_date' => $date]);
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM curated_links WHERE id = :id', ['id' => $id]);
    }

    public function findByItem(int $itemId): ?array
    {
        return $this->fetch('SELECT * FROM curated_links WHERE item_id = :item_id', ['item_id' => $itemId]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publishedForEditionDate(string $date, int $limit = 20): array
    {
        $sql = <<<'SQL'
SELECT cl.*, e.edition_date
FROM editions e
JOIN edition_curated_link ecl ON ecl.edition_id = e.id
JOIN curated_links cl ON cl.id = ecl.curated_link_id
WHERE e.edition_date = :edition_date
  AND cl.published_at IS NOT NULL
ORDER BY ecl.position ASC, cl.published_at DESC
LIMIT :limit
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':edition_date', $date);
        $statement->bindValue(':limit', max(1, min(100, $limit)), \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function latestPublishedEdition(): ?array
    {
        $sql = <<<'SQL'
SELECT e.*
FROM editions e
JOIN edition_curated_link ecl ON ecl.edition_id = e.id
JOIN curated_links cl ON cl.id = ecl.curated_link_id AND cl.published_at IS NOT NULL
ORDER BY e.edition_date DESC
LIMIT 1
SQL;

        return $this->fetch($sql);
    }

    public function create(array $attributes): int
    {
        $sql = <<<'SQL'
INSERT INTO curated_links (
    item_id, title, blurb, source_name, source_url, is_pinned, curator_notes, published_at
) VALUES (
    :item_id, :title, :blurb, :source_name, :source_url, :is_pinned, :curator_notes, :published_at
)
SQL;

        return $this->insert($sql, [
            'item_id' => $attributes['item_id'],
            'title' => $attributes['title'],
            'blurb' => $attributes['blurb'],
            'source_name' => $attributes['source_name'] ?? null,
            'source_url' => $attributes['source_url'] ?? null,
            'is_pinned' => (int) ($attributes['is_pinned'] ?? 0),
            'curator_notes' => $attributes['curator_notes'] ?? null,
            'published_at' => $attributes['published_at'] ?? null,
        ]);
    }

    public function attachToEdition(int $curatedLinkId, int $editionId, int $position): bool
    {
        $sql = <<<'SQL'
INSERT INTO edition_curated_link (edition_id, curated_link_id, position)
VALUES (:edition_id, :curated_link_id, :position)
ON DUPLICATE KEY UPDATE position = VALUES(position)
SQL;

        return $this->execute($sql, [
            'edition_id' => $editionId,
            'curated_link_id' => $curatedLinkId,
            'position' => $position,
        ]);
    }

    public function detachFromEditions(int $curatedLinkId): bool
    {
        $sql = 'DELETE FROM edition_curated_link WHERE curated_link_id = :curated_link_id';

        $this->execute($sql, ['curated_link_id' => $curatedLinkId]);

        return true;
    }

    public function update(int $id, array $attributes): bool
    {
        $sql = <<<'SQL'
UPDATE curated_links
SET title = :title,
    blurb = :blurb,
    source_name = :source_name,
    source_url = :source_url,
    is_pinned = :is_pinned,
    curator_notes = :curator_notes,
    published_at = :published_at,
    updated_at = CURRENT_TIMESTAMP
WHERE id = :id
SQL;

        return $this->execute($sql, [
            'id' => $id,
            'title' => $attributes['title'] ?? '',
            'blurb' => $attributes['blurb'] ?? '',
            'source_name' => $attributes['source_name'] ?? null,
            'source_url' => $attributes['source_url'] ?? null,
            'is_pinned' => (int) ($attributes['is_pinned'] ?? 0),
            'curator_notes' => $attributes['curator_notes'] ?? null,
            'published_at' => $attributes['published_at'] ?? null,
        ]);
    }

    public function nextPositionForEdition(int $editionId): int
    {
        $result = $this->fetch(
            'SELECT COALESCE(MAX(position) + 1, 1) AS next FROM edition_curated_link WHERE edition_id = :edition_id',
            ['edition_id' => $editionId]
        );

        return (int) ($result['next'] ?? 1);
    }

    public function stream(int $page = 1, int $perPage = 20): array
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT cl.*, e.edition_date FROM curated_links cl LEFT JOIN edition_curated_link ecl ON ecl.curated_link_id = cl.id LEFT JOIN editions e ON e.id = ecl.edition_id WHERE cl.published_at IS NOT NULL ORDER BY cl.published_at DESC, cl.created_at DESC LIMIT :limit OFFSET :offset';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function countStream(): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS aggregate FROM curated_links WHERE published_at IS NOT NULL');

        return (int) ($row['aggregate'] ?? 0);
    }

    public function streamCountForTag(int $tagId): int
    {
        $row = $this->fetch(
            'SELECT COUNT(*) AS aggregate FROM curated_link_tag clt JOIN curated_links cl ON cl.id = clt.curated_link_id WHERE clt.tag_id = :tag_id AND cl.published_at IS NOT NULL',
            ['tag_id' => $tagId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function streamForTag(int $tagId, int $page = 1, int $perPage = 20): array
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = <<<'SQL'
SELECT cl.*, e.edition_date
FROM curated_link_tag clt
JOIN curated_links cl ON cl.id = clt.curated_link_id
LEFT JOIN edition_curated_link ecl ON ecl.curated_link_id = cl.id
LEFT JOIN editions e ON e.id = ecl.edition_id
WHERE clt.tag_id = :tag_id
  AND cl.published_at IS NOT NULL
ORDER BY cl.published_at DESC, cl.created_at DESC
LIMIT :limit OFFSET :offset
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':tag_id', $tagId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }
}
