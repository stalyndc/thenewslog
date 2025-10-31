<?php

namespace App\Repositories;

use PDOException;

class CuratedLinkRepository extends BaseRepository
{
    private ?bool $supportsRichBlurbs = null;
    private ?int $blurbColumnLimit = null;
    private bool $blurbLimitLoaded = false;

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
ORDER BY cl.is_pinned DESC, cl.published_at DESC, cl.id DESC
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
ORDER BY cl.is_pinned DESC, ecl.position ASC, cl.published_at DESC
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
        $this->assertBlurbLengthWithinLimit((string) ($attributes['blurb'] ?? ''));

        return $this->insertCuratedLink($attributes, $this->supportsRichBlurbs());
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

    public function updateEditionPositions(int $editionId, array $positions): void
    {
        if (empty($positions)) {
            return;
        }

        $this->connection->beginTransaction();

        try {
            $statement = $this->connection->prepare('UPDATE edition_curated_link SET position = :position WHERE edition_id = :edition_id AND curated_link_id = :link_id');

            foreach ($positions as $linkId => $position) {
                $statement->execute([
                    'position' => max(1, (int) $position),
                    'edition_id' => $editionId,
                    'link_id' => (int) $linkId,
                ]);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function update(int $id, array $attributes): bool
    {
        $this->assertBlurbLengthWithinLimit((string) ($attributes['blurb'] ?? ''));

        return $this->performUpdate($id, $attributes, $this->supportsRichBlurbs());
    }

    public function nextPositionForEdition(int $editionId): int
    {
        $result = $this->fetch(
            'SELECT COALESCE(MAX(position) + 1, 1) AS next FROM edition_curated_link WHERE edition_id = :edition_id',
            ['edition_id' => $editionId]
        );

        return (int) ($result['next'] ?? 1);
    }

    public function pivotForCuratedLink(int $curatedLinkId): ?array
    {
        return $this->fetch(
            'SELECT edition_id, position FROM edition_curated_link WHERE curated_link_id = :curated_link_id LIMIT 1',
            ['curated_link_id' => $curatedLinkId]
        );
    }

    public function detachFromEdition(int $editionId, int $curatedLinkId): void
    {
        $pivot = $this->fetch(
            'SELECT position FROM edition_curated_link WHERE edition_id = :edition_id AND curated_link_id = :curated_link_id',
            [
                'edition_id' => $editionId,
                'curated_link_id' => $curatedLinkId,
            ]
        );

        if ($pivot === null) {
            return;
        }

        $position = (int) $pivot['position'];

        $this->connection->beginTransaction();

        try {
            $this->execute(
                'DELETE FROM edition_curated_link WHERE edition_id = :edition_id AND curated_link_id = :curated_link_id',
                [
                    'edition_id' => $editionId,
                    'curated_link_id' => $curatedLinkId,
                ]
            );

            $this->execute(
                'UPDATE edition_curated_link SET position = position - 1 WHERE edition_id = :edition_id AND position > :position',
                [
                    'edition_id' => $editionId,
                    'position' => $position,
                ]
            );

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function positionAfterPinned(int $editionId): int
    {
        $result = $this->fetch(
            'SELECT COUNT(*) AS pinned_count FROM edition_curated_link ecl JOIN curated_links cl ON cl.id = ecl.curated_link_id WHERE ecl.edition_id = :edition_id AND cl.is_pinned = 1',
            ['edition_id' => $editionId]
        );

        $count = (int) ($result['pinned_count'] ?? 0);

        return max(1, $count + 1);
    }

    private function insertCuratedLink(array $attributes, bool $includeRichHtml): int
    {
        $sql = $includeRichHtml
            ? <<<'SQL'
INSERT INTO curated_links (
    item_id, title, blurb, blurb_html, source_name, source_url, is_pinned, curator_notes, published_at
) VALUES (
    :item_id, :title, :blurb, :blurb_html, :source_name, :source_url, :is_pinned, :curator_notes, :published_at
)
SQL
            : <<<'SQL'
INSERT INTO curated_links (
    item_id, title, blurb, source_name, source_url, is_pinned, curator_notes, published_at
) VALUES (
    :item_id, :title, :blurb, :source_name, :source_url, :is_pinned, :curator_notes, :published_at
)
SQL;

        $parameters = [
            'item_id' => $attributes['item_id'] ?? null,
            'title' => $attributes['title'] ?? '',
            'blurb' => $attributes['blurb'] ?? '',
            'source_name' => $attributes['source_name'] ?? null,
            'source_url' => $attributes['source_url'] ?? null,
            'is_pinned' => (int) ($attributes['is_pinned'] ?? 0),
            'curator_notes' => $attributes['curator_notes'] ?? null,
            'published_at' => $attributes['published_at'] ?? null,
        ];

        if ($includeRichHtml) {
            $parameters['blurb_html'] = $attributes['blurb_html'] ?? null;
        }

        try {
            return $this->insert($sql, $parameters);
        } catch (PDOException $exception) {
            if ($includeRichHtml && $this->isUnknownColumn($exception, 'blurb_html')) {
                $this->supportsRichBlurbs = false;

                return $this->insertCuratedLink($attributes, false);
            }

            if ($this->isDataTooLongForColumn($exception, 'blurb')) {
                throw new \InvalidArgumentException(
                    $this->blurbLengthErrorMessage(),
                    0,
                    $exception
                );
            }

            throw $exception;
        }
    }

    private function performUpdate(int $id, array $attributes, bool $includeRichHtml): bool
    {
        $sql = $includeRichHtml
            ? <<<'SQL'
UPDATE curated_links
SET title = :title,
    blurb = :blurb,
    blurb_html = :blurb_html,
    source_name = :source_name,
    source_url = :source_url,
    is_pinned = :is_pinned,
    curator_notes = :curator_notes,
    published_at = :published_at,
    updated_at = CURRENT_TIMESTAMP
WHERE id = :id
SQL
            : <<<'SQL'
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

        $parameters = [
            'id' => $id,
            'title' => $attributes['title'] ?? '',
            'blurb' => $attributes['blurb'] ?? '',
            'source_name' => $attributes['source_name'] ?? null,
            'source_url' => $attributes['source_url'] ?? null,
            'is_pinned' => (int) ($attributes['is_pinned'] ?? 0),
            'curator_notes' => $attributes['curator_notes'] ?? null,
            'published_at' => $attributes['published_at'] ?? null,
        ];

        if ($includeRichHtml) {
            $parameters['blurb_html'] = $attributes['blurb_html'] ?? null;
        }

        try {
            return $this->execute($sql, $parameters);
        } catch (PDOException $exception) {
            if ($includeRichHtml && $this->isUnknownColumn($exception, 'blurb_html')) {
                $this->supportsRichBlurbs = false;

                return $this->performUpdate($id, $attributes, false);
            }

            if ($this->isDataTooLongForColumn($exception, 'blurb')) {
                throw new \InvalidArgumentException(
                    $this->blurbLengthErrorMessage(),
                    0,
                    $exception
                );
            }

            throw $exception;
        }
    }

    private function supportsRichBlurbs(): bool
    {
        if ($this->supportsRichBlurbs !== null) {
            return $this->supportsRichBlurbs;
        }

        try {
            $statement = $this->connection->query(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'curated_links' AND COLUMN_NAME = 'blurb_html' LIMIT 1"
            );

            $this->supportsRichBlurbs = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable) {
            // If we cannot detect support, optimistically assume it's available and fall back on error handling.
            $this->supportsRichBlurbs = true;
        }

        return $this->supportsRichBlurbs;
    }

    private function assertBlurbLengthWithinLimit(string $blurb): void
    {
        $limit = $this->blurbCharacterLimit();

        if ($limit === null || $limit <= 0) {
            return;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($blurb, 'UTF-8') : strlen($blurb);

        if ($length > $limit) {
            throw new \InvalidArgumentException($this->blurbLengthErrorMessage());
        }
    }

    private function blurbCharacterLimit(): ?int
    {
        if ($this->blurbLimitLoaded) {
            return $this->blurbColumnLimit;
        }

        try {
            $statement = $this->connection->query(
                "SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'curated_links' AND COLUMN_NAME = 'blurb' LIMIT 1"
            );

            $value = $statement !== false ? $statement->fetchColumn() : false;
            $this->blurbColumnLimit = ($value === false || $value === null) ? null : (int) $value;
        } catch (\Throwable) {
            $this->blurbColumnLimit = null;
        }

        $this->blurbLimitLoaded = true;

        return $this->blurbColumnLimit;
    }

    private function blurbLengthErrorMessage(): string
    {
        $limit = $this->blurbColumnLimit;

        if ($limit !== null && $limit > 0) {
            return sprintf(
                'The summary is too long for the current database schema (max %d characters). Please shorten it or run scripts/upgrade_20251027_rich_blurb.sql to upgrade the curated_links table.',
                $limit
            );
        }

        return 'The summary is too long for the current database schema. Please shorten it or run scripts/upgrade_20251027_rich_blurb.sql to upgrade the curated_links table.';
    }

    private function isUnknownColumn(PDOException $exception, string $column): bool
    {
        if ($exception->getCode() === '42S22') {
            return true;
        }

        $message = $exception->getMessage();

        return stripos($message, sprintf("Unknown column '%s'", $column)) !== false;
    }

    private function isDataTooLongForColumn(PDOException $exception, string $column): bool
    {
        if ($exception->getCode() === '22001') {
            return true;
        }

        $message = $exception->getMessage();

        return stripos($message, sprintf("Data too long for column '%s'", $column)) !== false;
    }

    public function attachToEditionAtPosition(int $curatedLinkId, int $editionId, int $position): void
    {
        $position = max(1, $position);

        $this->connection->beginTransaction();

        try {
            $this->execute(
                'UPDATE edition_curated_link SET position = position + 1 WHERE edition_id = :edition_id AND position >= :position',
                [
                    'edition_id' => $editionId,
                    'position' => $position,
                ]
            );

            $this->execute(
                'INSERT INTO edition_curated_link (edition_id, curated_link_id, position) VALUES (:edition_id, :curated_link_id, :position)',
                [
                    'edition_id' => $editionId,
                    'curated_link_id' => $curatedLinkId,
                    'position' => $position,
                ]
            );

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function attachToEditionAtTop(int $curatedLinkId, int $editionId): void
    {
        $this->attachToEditionAtPosition($curatedLinkId, $editionId, 1);
    }

    public function moveToTopOfEdition(int $curatedLinkId, int $editionId): void
    {
        $pivot = $this->fetch(
            'SELECT position FROM edition_curated_link WHERE edition_id = :edition_id AND curated_link_id = :curated_link_id',
            [
                'edition_id' => $editionId,
                'curated_link_id' => $curatedLinkId,
            ]
        );

        if ($pivot === null) {
            $this->attachToEditionAtTop($curatedLinkId, $editionId);

            return;
        }

        $position = (int) $pivot['position'];

        $this->connection->beginTransaction();

        try {
            $this->execute(
                'DELETE FROM edition_curated_link WHERE edition_id = :edition_id AND curated_link_id = :curated_link_id',
                [
                    'edition_id' => $editionId,
                    'curated_link_id' => $curatedLinkId,
                ]
            );

            $this->execute(
                'UPDATE edition_curated_link SET position = position - 1 WHERE edition_id = :edition_id AND position > :position',
                [
                    'edition_id' => $editionId,
                    'position' => $position,
                ]
            );

            $this->execute(
                'UPDATE edition_curated_link SET position = position + 1 WHERE edition_id = :edition_id',
                [
                    'edition_id' => $editionId,
                ]
            );

            $this->execute(
                'INSERT INTO edition_curated_link (edition_id, curated_link_id, position) VALUES (:edition_id, :curated_link_id, 1)',
                [
                    'edition_id' => $editionId,
                    'curated_link_id' => $curatedLinkId,
                ]
            );

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function setPinned(int $curatedLinkId, bool $isPinned): bool
    {
        return $this->execute(
            'UPDATE curated_links SET is_pinned = :is_pinned, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
            [
                'id' => $curatedLinkId,
                'is_pinned' => $isPinned ? 1 : 0,
            ]
        );
    }

    public function publishAllForEdition(int $editionId, ?string $publishedAt = null): void
    {
        $timestamp = $publishedAt ?? date('Y-m-d H:i:s');

        $this->execute(
            <<<'SQL'
UPDATE curated_links cl
JOIN edition_curated_link ecl ON ecl.curated_link_id = cl.id
SET cl.published_at = :published_at,
    cl.is_published = 1,
    cl.updated_at = CURRENT_TIMESTAMP
WHERE ecl.edition_id = :edition_id
  AND cl.published_at IS NULL
SQL,
            [
                'edition_id' => $editionId,
                'published_at' => $timestamp,
            ]
        );
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
ORDER BY cl.is_pinned DESC, cl.published_at DESC, cl.created_at DESC
LIMIT :limit OFFSET :offset
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':tag_id', $tagId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function delete(int $id): bool
    {
        $this->detachFromEditions($id);

        return $this->execute('DELETE FROM curated_links WHERE id = :id', ['id' => $id]);
    }
}
