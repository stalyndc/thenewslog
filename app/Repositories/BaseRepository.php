<?php

namespace App\Repositories;

use PDO;
use PDOStatement;

abstract class BaseRepository
{
    protected PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function setConnection(PDO $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchAll(string $sql, array $parameters = []): array
    {
        $statement = $this->prepare($sql, $parameters);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>|null
     */
    protected function fetch(string $sql, array $parameters = []): ?array
    {
        $statement = $this->prepare($sql, $parameters);
        $result = $statement->fetch();

        return $result === false ? null : $result;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function execute(string $sql, array $parameters = []): bool
    {
        $statement = $this->prepare($sql, $parameters);

        return true;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function insert(string $sql, array $parameters = []): int
    {
        $this->prepare($sql, $parameters);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function prepare(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement;
    }
}
