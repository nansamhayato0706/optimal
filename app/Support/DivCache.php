<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class DivCache
{
    /** @var array|null */
    private static $cache = null;

    public static function load(PDO $pdo): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $stmt = $pdo->query(
            'SELECT div_parent_id, div_id, div_name FROM mst_div WHERE show_flg = 0 ORDER BY div_parent_id, div_id'
        );
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(string) $row['div_parent_id']][(int) $row['div_id']] = (string) $row['div_name'];
        }
        self::$cache = $result;
        return self::$cache;
    }
}
