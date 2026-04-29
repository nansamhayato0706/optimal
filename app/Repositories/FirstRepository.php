<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class FirstRepository extends AbstractRepository
{
    public function findUserByLoginToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_uuid, group_uuid FROM mst_user WHERE login_uuid = :login_uuid AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['login_uuid' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findGroupSummary(string $groupUuid): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT group_name, notification FROM mst_group WHERE group_uuid = :group_uuid AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['group_uuid' => $groupUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findClientLinks(string $groupUuid): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT link_url, link_name FROM mst_link WHERE group_uuid = :group_uuid ORDER BY sort, link_name'
        );
        $stmt->execute(['group_uuid' => $groupUuid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
