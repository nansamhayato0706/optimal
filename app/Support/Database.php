<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class Database
{
    private static $pdo = null;

    public static function initialize(PdoFactory $factory): void
    {
        if (self::$pdo === null) {
            self::$pdo = $factory->create();
        }
    }

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database not initialized. Call Database::initialize() first.');
        }
        return self::$pdo;
    }
}
