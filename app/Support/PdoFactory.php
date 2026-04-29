<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class PdoFactory
{
    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    public function create(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $this->config->dbHost(),
            $this->config->dbName()
        );
        $pdo = new PDO($dsn, $this->config->dbUser(), $this->config->dbPass(), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    }
}
