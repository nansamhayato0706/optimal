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
