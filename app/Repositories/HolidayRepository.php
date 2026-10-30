<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Support\AppConfig;
use PDO;

/**
 * 外部DB（既定: jigyodan_kouzono）の public_holiday を読み取り専用で参照する。
 * ZAITAKU_EXT_DB_USER が未設定なら本体DBの接続にDB名を付けてクエリする（同一サーバー・権限あり前提）。
 * 設定されていれば、外部DB専用の接続（別ユーザー/別ホスト可）を使う。
 */
final class HolidayRepository extends AbstractRepository implements HolidayRepositoryInterface
{
    private $config;
    private $dbName;

    public function __construct(AppConfig $config, ?PDO $pdo = null)
    {
        parent::__construct($pdo);
        $this->config = $config;
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
            $pdo = $this->connection();
            $stmt = $pdo->prepare(
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
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $holidays[(string) $row['holiday_date']] = (string) $row['public_holiday_name'];
        }
        return $holidays;
    }

    private function connection(): PDO
    {
        if (!$this->config->extDbSeparateConnection()) {
            return $this->pdo;
        }

        $host = $this->config->extDbHost() !== '' ? $this->config->extDbHost() : $this->config->dbHost();
        return new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $this->dbName),
            $this->config->extDbUser(),
            $this->config->extDbPass(),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
}
