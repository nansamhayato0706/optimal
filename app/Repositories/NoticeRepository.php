<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NoticeRepository extends AbstractRepository
{
    public function findNoticeByGroup(string $groupUuid): ?array
    {
        if ($groupUuid === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT group_uuid, group_name, notification FROM mst_group WHERE group_uuid = :group_uuid LIMIT 1'
        );
        $stmt->execute(['group_uuid' => $groupUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function updateNotice(string $groupUuid, string $notification, string $actorUuid): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mst_group'
            . ' SET notification = :notification, update_date = NOW(), update_uuid = :actor_uuid'
            . ' WHERE group_uuid = :group_uuid'
        );
        return $stmt->execute([
            'notification' => $notification,
            'actor_uuid' => $actorUuid,
            'group_uuid' => $groupUuid,
        ]);
    }
}
