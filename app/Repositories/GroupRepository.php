<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\GroupRepositoryInterface;
use PDO;

final class GroupRepository extends AbstractRepository implements GroupRepositoryInterface
{
    public function findGroups(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM mst_group WHERE delete_flg = 0 ORDER BY group_name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findGroupByUuid(string $groupUuid): ?array
    {
        if ($groupUuid === '') {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM mst_group WHERE group_uuid = :group_uuid LIMIT 1');
        $stmt->execute(['group_uuid' => $groupUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function saveGroup(array $group, string $actorUuid): bool
    {
        try {
            if (($group['group_uuid'] ?? '') === '') {
                $group['group_uuid'] = $this->generateUuid();
                $this->insertGroup($group, $actorUuid);
            } else {
                $this->updateGroup($group, $actorUuid);
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function insertGroup(array $group, string $actorUuid): void
    {
        $sql = 'INSERT INTO mst_group ('
            . ' group_uuid, group_name, group_name_kana, group_zip_code, group_prefecture_div, group_address,'
            . ' group_tel, group_email, notification, remark, delete_flg, insert_date, insert_uuid, update_date, update_uuid'
            . ' ) VALUES ('
            . ' :group_uuid, :group_name, :group_name_kana, :group_zip_code, :group_prefecture_div, :group_address,'
            . ' :group_tel, :group_email, :notification, :remark, 0, NOW(), :actor_uuid, NOW(), :actor_uuid'
            . ' )';
        $this->pdo->prepare($sql)->execute($this->normalizeParams($group, $actorUuid));
    }

    private function updateGroup(array $group, string $actorUuid): void
    {
        $sql = 'UPDATE mst_group SET'
            . ' group_name = :group_name, group_name_kana = :group_name_kana, group_zip_code = :group_zip_code,'
            . ' group_prefecture_div = :group_prefecture_div, group_address = :group_address, group_tel = :group_tel,'
            . ' group_email = :group_email, notification = :notification, remark = :remark,'
            . ' update_date = NOW(), update_uuid = :actor_uuid'
            . ' WHERE group_uuid = :group_uuid';
        $this->pdo->prepare($sql)->execute($this->normalizeParams($group, $actorUuid));
    }

    private function normalizeParams(array $group, string $actorUuid): array
    {
        return [
            'group_uuid'           => (string) ($group['group_uuid'] ?? ''),
            'group_name'           => (string) ($group['group_name'] ?? ''),
            'group_name_kana'      => (string) ($group['group_name_kana'] ?? ''),
            'group_zip_code'       => (string) ($group['group_zip_code'] ?? ''),
            'group_prefecture_div' => (int)    ($group['group_prefecture_div'] ?? 0),
            'group_address'        => (string) ($group['group_address'] ?? ''),
            'group_tel'            => (string) ($group['group_tel'] ?? ''),
            'group_email'          => (string) ($group['group_email'] ?? ''),
            'notification'         => (string) ($group['notification'] ?? ''),
            'remark'               => (string) ($group['remark'] ?? ''),
            'actor_uuid'           => $actorUuid,
        ];
    }
}
