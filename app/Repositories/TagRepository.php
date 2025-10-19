<?php

namespace App\Repositories;

use App\Helpers\Str;

class TagRepository extends BaseRepository
{
    public function allWithCounts(): array
    {
        $sql = <<<'SQL'
SELECT t.*, COUNT(clt.curated_link_id) AS link_count
FROM tags t
LEFT JOIN curated_link_tag clt ON clt.tag_id = t.id
GROUP BY t.id
ORDER BY t.name ASC
SQL;

        return $this->fetchAll($sql);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetch('SELECT * FROM tags WHERE slug = :slug', ['slug' => $slug]);
    }

    public function ensure(string $name): array
    {
        $slug = Str::slug($name);
        $existing = $this->findBySlug($slug);

        if ($existing !== null) {
            return $existing;
        }

        $this->insert(
            'INSERT INTO tags (name, slug) VALUES (:name, :slug)',
            ['name' => $name, 'slug' => $slug]
        );

        return $this->findBySlug($slug) ?? ['name' => $name, 'slug' => $slug];
    }

    /**
     * @param array<int, int> $tagIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function tagsForIds(array $tagIds): array
    {
        if (empty($tagIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($tagIds), '?'));

        $statement = $this->connection->prepare("SELECT * FROM tags WHERE id IN ($placeholders)");
        $statement->execute(array_values($tagIds));

        return $statement->fetchAll() ?: [];
    }

    /**
     * @param array<int> $linkIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function tagsForCuratedLinks(array $linkIds): array
    {
        if (empty($linkIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($linkIds), '?'));

        $sql = <<<'SQL'
SELECT clt.curated_link_id, t.*
FROM curated_link_tag clt
JOIN tags t ON t.id = clt.tag_id
WHERE clt.curated_link_id IN (%s)
ORDER BY t.name ASC
SQL;
        $sql = sprintf($sql, $placeholders);

        $statement = $this->connection->prepare($sql);
        $statement->execute(array_values($linkIds));

        $rows = $statement->fetchAll() ?: [];
        $grouped = [];

        foreach ($rows as $row) {
            $linkId = (int) $row['curated_link_id'];
            $grouped[$linkId][] = $row;
        }

        return $grouped;
    }

    /**
     * @param array<string> $names
     */
    public function syncForCuratedLink(int $curatedLinkId, array $names): void
    {
        $normalizedNames = [];

        foreach ($names as $name) {
            $trimmed = trim($name);
            if ($trimmed !== '') {
                $normalizedNames[] = $trimmed;
            }
        }

        $uniqueNames = [];
        $seen = [];

        foreach ($normalizedNames as $name) {
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $uniqueNames[] = $name;
        }

        $this->connection->beginTransaction();

        try {
            $desiredIds = [];

            foreach ($uniqueNames as $name) {
                $tag = $this->ensure($name);
                $desiredIds[] = (int) $tag['id'];
            }

            $delete = $this->connection->prepare('DELETE FROM curated_link_tag WHERE curated_link_id = :id');
            $delete->execute(['id' => $curatedLinkId]);

            if (!empty($desiredIds)) {
                $insert = $this->connection->prepare('INSERT INTO curated_link_tag (curated_link_id, tag_id) VALUES (:link_id, :tag_id)');
                foreach ($desiredIds as $tagId) {
                    $insert->execute([
                        'link_id' => $curatedLinkId,
                        'tag_id' => $tagId,
                    ]);
                }
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }
}
