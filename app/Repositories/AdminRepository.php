<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\AdminRepositoryInterface;
use PDO;

final class AdminRepository extends AbstractRepository implements AdminRepositoryInterface
{
    public function findAdminByCredentials(string $adminId, string $adminPassword): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM mst_admin WHERE admin_id = :admin_id AND admin_password = :admin_password AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['admin_id' => $adminId, 'admin_password' => $adminPassword]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findAdminByUuid(string $adminUuid): ?array
    {
        if ($adminUuid === '') {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM mst_admin WHERE admin_uuid = :admin_uuid LIMIT 1');
        $stmt->execute(['admin_uuid' => $adminUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function groupExists(string $groupUuid): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM mst_group WHERE group_uuid = :group_uuid LIMIT 1');
        $stmt->execute(['group_uuid' => $groupUuid]);
        return $stmt->fetchColumn() !== false;
    }

    public function findAdmins(string $groupUuid): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM mst_admin WHERE group_uuid = :group_uuid AND admin_div > 1 AND delete_flg = 0 ORDER BY admin_uuid'
        );
        $stmt->execute(['group_uuid' => $groupUuid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adminIdExists(string $adminId, ?string $excludeAdminUuid = null): bool
    {
        $sql    = 'SELECT 1 FROM mst_admin WHERE admin_id = :admin_id';
        $params = ['admin_id' => $adminId];
        if ($excludeAdminUuid !== null && $excludeAdminUuid !== '') {
            $sql .= ' AND admin_uuid <> :exclude_admin_uuid';
            $params['exclude_admin_uuid'] = $excludeAdminUuid;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    public function saveAdmin(array $admin, string $actorUuid): bool
    {
        try {
            if (($admin['admin_uuid'] ?? '') === '') {
                $admin['admin_uuid'] = $this->generateUuid();
                $this->insertAdmin($admin, $actorUuid);
            } else {
                $this->updateAdmin($admin, $actorUuid);
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function insertAdmin(array $admin, string $actorUuid): void
    {
        $sql = 'INSERT INTO mst_admin ('
            . ' admin_uuid, group_uuid, admin_id, admin_password, admin_div, admin_name, admin_name_kana, admin_tel, admin_email, remark,'
            . ' delete_flg, insert_date, insert_uuid, update_date, update_uuid'
            . ' ) VALUES ('
            . ' :admin_uuid, :group_uuid, :admin_id, :admin_password, :admin_div, :admin_name, :admin_name_kana, :admin_tel, :admin_email, :remark,'
            . ' 0, NOW(), :actor_uuid, NOW(), :actor_uuid'
            . ' )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->normalizeParams($admin, $actorUuid));
    }

    private function updateAdmin(array $admin, string $actorUuid): void
    {
        $sql = 'UPDATE mst_admin SET'
            . ' group_uuid = :group_uuid, admin_id = :admin_id, admin_password = :admin_password, admin_div = :admin_div,'
            . ' admin_name = :admin_name, admin_name_kana = :admin_name_kana, admin_tel = :admin_tel, admin_email = :admin_email,'
            . ' remark = :remark, update_date = NOW(), update_uuid = :actor_uuid'
            . ' WHERE admin_uuid = :admin_uuid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->normalizeParams($admin, $actorUuid));
    }

    private function normalizeParams(array $admin, string $actorUuid): array
    {
        return [
            'admin_uuid'      => (string) ($admin['admin_uuid'] ?? ''),
            'group_uuid'      => (string) ($admin['group_uuid'] ?? ''),
            'admin_id'        => (string) ($admin['admin_id'] ?? ''),
            'admin_password'  => (string) ($admin['admin_password'] ?? ''),
            'admin_div'       => (int)    ($admin['admin_div'] ?? 0),
            'admin_name'      => (string) ($admin['admin_name'] ?? ''),
            'admin_name_kana' => (string) ($admin['admin_name_kana'] ?? ''),
            'admin_tel'       => (string) ($admin['admin_tel'] ?? ''),
            'admin_email'     => (string) ($admin['admin_email'] ?? ''),
            'remark'          => (string) ($admin['remark'] ?? ''),
            'actor_uuid'      => $actorUuid,
        ];
    }
}
