<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Support\AppConfig;

/**
 * 外部DB（既定: jigyodan_kouzono）の public_holiday を読み取り専用で参照する。
 * 同一MySQLサーバー上のため、既存の接続に DB名を付けてクエリする。
 */
final class HolidayRepository extends AbstractRepository implements HolidayRepositoryInterface
{
    private $dbName;

    public function __construct(AppConfig $config, ?\PDO $pdo = null)
    {
        parent::__construct($pdo);
        $this->dbName = $config->extDbName();
    }

    public function findHolidays(string $fromDate, string $toDateExclusive): array
    {
        // DB名はSQLに埋め込むため、英数字とアンダースコアのみ許可する
        if (preg_match('/^[A-Za-z0-9_]+$/', $this->dbName) !== 1) {
            error_log('[holiday] invalid external db name');
            return [];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT holiday_date, public_holiday_name FROM `' . $this->dbName . '`.public_holiday'
                . ' WHERE holiday_date >= :from_date AND holiday_date < :to_date'
            );
            $stmt->execute(['from_date' => $fromDate, 'to_date' => $toDateExclusive]);
        } catch (\PDOException $e) {
            // 外部DBが参照できなくても画面は表示する（祝日なし扱い）
            error_log('[holiday] lookup failed: ' . $e->getMessage());
            return [];
        }

        $holidays = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $holidays[(string) $row['holiday_date']] = (string) $row['public_holiday_name'];
        }
        return $holidays;
    }
}
