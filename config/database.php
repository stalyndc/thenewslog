<?php

return [
    'driver' => getenv('DB_DRIVER') ?: 'mysql',
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'thenewslog',
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
    'socket' => getenv('DB_SOCKET') ?: null,
];
