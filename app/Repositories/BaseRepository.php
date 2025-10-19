<?php

namespace App\Repositories;

use PDO;

abstract class BaseRepository
{
    protected PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }
}
