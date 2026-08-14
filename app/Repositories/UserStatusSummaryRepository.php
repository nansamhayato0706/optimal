<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserStatusSummaryRepository extends AbstractRepository
{
    public function refreshUserStatusSummary(string $userUuid): bool
    {
        return parent::refreshUserStatusSummary($userUuid);
    }

    public function refreshAllUserStatusSummaries(): int
    {
        $stmt = $this->pdo->query('SELECT user_uuid FROM mst_user');
        return $this->refreshMany($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function refreshActiveByGroup(string $groupUuid): int
    {
        if ($groupUuid === '') {
            return 0;
        }

        $stmt = $this->pdo->prepare('SELECT user_uuid FROM mst_user WHERE group_uuid = :group_uuid AND delete_flg = 0');
        $stmt->execute(['group_uuid' => $groupUuid]);
        return $this->refreshMany($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function fetchGroupStatusSignature(string $groupUuid, string $adminUuid, string $deleteFlag): string
    {
        $sql = 'SELECT m1.user_uuid, COALESCE(GREATEST('
             . "  IFNULL(s.contact_touch_at, '1000-01-01 00:00:00'),"
             . "  IFNULL(s.report_touch_at, '1000-01-01 00:00:00'),"
             . "  IFNULL(s.chat_touch_at, '1000-01-01 00:00:00'),"
             . "  IFNULL(s.updated_at, '1000-01-01 00:00:00')"
             . "), '') AS last_touch"
             . ' FROM mst_user_admin m1'
             . ' JOIN mst_user m2 ON m1.user_uuid = m2.user_uuid'
             . ' LEFT JOIN tbl_user_status_summary s ON s.user_uuid = m2.user_uuid'
             . ' WHERE m2.group_uuid = :group_uuid AND m1.admin_uuid = :admin_uuid AND m2.delete_flg = :delete_flg'
             . ' ORDER BY m1.user_uuid ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'group_uuid' => $groupUuid,
            'admin_uuid' => $adminUuid,
            'delete_flg' => $deleteFlag,
        ]);

        $count = 0;
        $userHash = '';
        $lastTouch = '';
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $count++;
            $userHash = sha1($userHash . '|' . (string) ($row['user_uuid'] ?? ''));
            $rowTouch = (string) ($row['last_touch'] ?? '');
            if ($rowTouch !== '' && strcmp($rowTouch, $lastTouch) > 0) {
                $lastTouch = $rowTouch;
            }
        }

        return implode('|', [
            (string) $count,
            $userHash,
            $lastTouch,
        ]);
    }

    private function refreshMany(array $userUuids): int
    {
        $count = 0;
        foreach ($userUuids as $userUuid) {
            if ($this->refreshUserStatusSummary((string) $userUuid)) {
                $count++;
            }
        }

        return $count;
    }
}
