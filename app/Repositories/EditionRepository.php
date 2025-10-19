<?php

namespace App\Repositories;

use App\Helpers\Str;

class EditionRepository extends BaseRepository
{
    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM editions WHERE id = :id', ['id' => $id]);
    }

    public function findByDate(string $date): ?array
    {
        return $this->fetch('SELECT * FROM editions WHERE edition_date = :edition_date', [
            'edition_date' => $date,
        ]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetch('SELECT * FROM editions WHERE slug = :slug', ['slug' => $slug]);
    }

    public function create(string $date, string $title, string $status = 'draft'): int
    {
        $slug = Str::slug($date);

        $sql = <<<'SQL'
INSERT INTO editions (edition_date, slug, title, status)
VALUES (:edition_date, :slug, :title, :status)
SQL;

        return $this->insert($sql, [
            'edition_date' => $date,
            'slug' => $slug,
            'title' => $title,
            'status' => $status,
        ]);
    }

    public function ensureForDate(string $date, ?string $title = null): array
    {
        $edition = $this->findByDate($date);

        if ($edition !== null) {
            return $edition;
        }

        $timestamp = strtotime($date);
        $friendlyDate = $timestamp ? date('F j, Y', $timestamp) : $date;
        $title ??= sprintf('Edition %s', $friendlyDate);
        $id = $this->create($date, $title);

        return $this->find($id) ?? [];
    }

    public function findByCuratedLink(int $curatedLinkId): ?array
    {
        $sql = <<<'SQL'
SELECT e.*
FROM editions e
JOIN edition_curated_link ecl ON ecl.edition_id = e.id
WHERE ecl.curated_link_id = :curated_link_id
ORDER BY ecl.position ASC
LIMIT 1
SQL;

        return $this->fetch($sql, ['curated_link_id' => $curatedLinkId]);
    }

    public function updateStatus(int $editionId, string $status): void
    {
        $allowed = ['draft', 'scheduled', 'published'];

        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid edition status "%s"', $status));
        }

        $publishedAt = null;

        if ($status === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $this->execute(
            'UPDATE editions SET status = :status, published_at = :published_at, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
            [
                'status' => $status,
                'published_at' => $publishedAt,
                'id' => $editionId,
            ]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publishedWithCounts(int $page = 1, int $perPage = 12): array
    {
        $perPage = max(1, min(50, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = <<<'SQL'
SELECT e.*, COUNT(DISTINCT cl.id) AS link_count
FROM editions e
JOIN edition_curated_link ecl ON ecl.edition_id = e.id
JOIN curated_links cl ON cl.id = ecl.curated_link_id AND cl.published_at IS NOT NULL
GROUP BY e.id
ORDER BY e.edition_date DESC
LIMIT :limit OFFSET :offset
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function countPublished(): int
    {
        $row = $this->fetch(
            'SELECT COUNT(DISTINCT e.id) AS aggregate FROM editions e JOIN edition_curated_link ecl ON e.id = ecl.edition_id JOIN curated_links cl ON cl.id = ecl.curated_link_id AND cl.published_at IS NOT NULL'
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function findPublishedByDate(string $date): ?array
    {
        $sql = <<<'SQL'
SELECT e.*
FROM editions e
JOIN edition_curated_link ecl ON ecl.edition_id = e.id
JOIN curated_links cl ON cl.id = ecl.curated_link_id AND cl.published_at IS NOT NULL
WHERE e.edition_date = :edition_date
LIMIT 1
SQL;

        return $this->fetch($sql, ['edition_date' => $date]);
    }
}
