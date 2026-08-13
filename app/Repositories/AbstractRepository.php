<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use App\Support\DivCache;
use App\Support\Uuid;
use PDO;

abstract class AbstractRepository
{
    protected $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    protected function generateUuid(): string
    {
        return Uuid::v4();
    }

    public function findDivMap(): array
    {
        return DivCache::load($this->pdo);
    }

    protected function refreshUserStatusSummary(string $userUuid): bool
    {
        if ($userUuid === '') {
            return false;
        }
        $sql = 'REPLACE INTO tbl_user_status_summary ('
             . ' user_uuid, contact_uuid, contact_div, confirm_div, contact_date, contact_touch_at,'
             . ' report_uuid, report_date, charge_comment, report_touch_at,'
             . ' chat_user_uuid, chat_touch_at, updated_at'
             . ' )'
             . ' SELECT u.user_uuid,'
             . ' c.contact_uuid, c.contact_div, c.confirm_div, c.contact_date, c.contact_touch_at,'
             . ' r.report_uuid, r.report_date, r.charge_comment, r.report_touch_at,'
             . ' ch.chat_user_uuid, ch.chat_touch_at, NOW()'
             . ' FROM (SELECT :user_uuid AS user_uuid) u'
             . ' LEFT JOIN ('
             . '   SELECT contact_uuid, contact_div, confirm_div, contact_date,'
             . "          GREATEST(IFNULL(insert_date,'1000-01-01'),IFNULL(update_date,'1000-01-01'),"
             . "                   IFNULL(confirm_date,'1000-01-01'),IFNULL(comment_date,'1000-01-01')) AS contact_touch_at"
             . '   FROM tbl_contact WHERE insert_uuid = :contact_user_uuid AND delete_flg = 0'
             . '     AND (contact_div < 4 OR contact_div = 5)'
             . '   ORDER BY insert_date DESC, contact_uuid DESC LIMIT 1'
             . ' ) c ON 1=1'
             . ' LEFT JOIN ('
             . "   SELECT report_uuid, report_date, charge_comment, COALESCE(update_date, insert_date, CONCAT(report_date,' 00:00:00')) AS report_touch_at"
             . '   FROM tbl_report WHERE user_uuid = :report_user_uuid AND delete_flg = 0'
             . '   ORDER BY report_date DESC, report_uuid DESC LIMIT 1'
             . ' ) r ON 1=1'
             . ' LEFT JOIN ('
             . '   SELECT user_uuid AS chat_user_uuid, MAX(COALESCE(update_date, insert_date)) AS chat_touch_at'
             . '   FROM tbl_chat WHERE user_uuid = :chat_user_uuid AND admin_chat_div = 1 AND delete_flg = 0 GROUP BY user_uuid'
             . ' ) ch ON 1=1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_uuid'         => $userUuid,
            'contact_user_uuid' => $userUuid,
            'report_user_uuid'  => $userUuid,
            'chat_user_uuid'    => $userUuid,
        ]);
        return true;
    }
}
