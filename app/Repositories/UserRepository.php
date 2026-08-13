<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\UserRepositoryInterface;
use PDO;

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function adminExists(string $adminUuid): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM mst_admin WHERE admin_uuid = :admin_uuid LIMIT 1');
        $stmt->execute(['admin_uuid' => $adminUuid]);
        return $stmt->fetchColumn() !== false;
    }

    public function userAssignedToAdmin(string $userUuid, string $adminUuid): bool
    {
        if ($userUuid === '' || $adminUuid === '') {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM mst_user_admin WHERE user_uuid = :user_uuid AND admin_uuid = :admin_uuid LIMIT 1'
        );
        $stmt->execute(['user_uuid' => $userUuid, 'admin_uuid' => $adminUuid]);
        return $stmt->fetchColumn() !== false;
    }

    public function findAdminBySessionUuid(string $sessionUuid): ?array
    {
        if ($sessionUuid === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT * FROM mst_admin WHERE session_uuid = :session_uuid AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['session_uuid' => $sessionUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findAdminByCredentials(string $adminId, string $password): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM mst_admin WHERE admin_id = :admin_id AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['admin_id' => $adminId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        // パスワード検証（移行完了後は password_verify に変える）
        if ((string) $row['admin_password'] !== $password) {
            return null;
        }
        return $row;
    }

    public function updateAdminSessionUuid(string $adminUuid, string $sessionUuid): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mst_admin SET session_uuid = :session_uuid WHERE admin_uuid = :admin_uuid'
        );
        $stmt->execute(['session_uuid' => $sessionUuid, 'admin_uuid' => $adminUuid]);
    }

    public function findUsers(string $groupUuid, string $adminUuid, string $deleteFlag): array
    {
        $sql = 'SELECT m1.user_uuid, m1.admin_uuid, m2.group_uuid, m2.user_id, m2.user_div, m2.work_style_div,'
             . ' m2.user_name, m2.sex_div, m2.birthday, m2.user_area, m2.user_address, m2.user_tel, m2.delete_flg,'
             . ' s.contact_uuid, s.contact_div, s.confirm_div, s.contact_date,'
             . ' CASE WHEN s.report_date = CURDATE() THEN s.report_uuid ELSE NULL END AS report_uuid,'
             . ' CASE WHEN s.report_date = CURDATE() THEN r.admin_uuid ELSE NULL END AS report_admin_uuid,'
             . ' CASE WHEN s.report_date = CURDATE() THEN s.charge_comment ELSE NULL END AS charge_comment,'
             . ' s.chat_user_uuid'
             . ' FROM mst_user_admin m1'
             . ' JOIN mst_user m2 ON m1.user_uuid = m2.user_uuid'
             . ' LEFT JOIN tbl_user_status_summary s ON s.user_uuid = m2.user_uuid'
             . ' LEFT JOIN tbl_report r ON r.report_uuid = s.report_uuid AND r.delete_flg = 0'
             . ' WHERE m2.group_uuid = :group_uuid AND m1.admin_uuid = :admin_uuid AND m2.delete_flg = :delete_flg'
             . ' ORDER BY m2.work_style_div ASC, m2.user_id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'group_uuid'  => $groupUuid,
            'admin_uuid'  => $adminUuid,
            'delete_flg'  => $deleteFlag,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUserStatuses(string $groupUuid, string $adminUuid, string $deleteFlag): array
    {
        $sql = 'SELECT m2.user_uuid,'
             . ' s.contact_uuid, s.contact_div, s.confirm_div, s.contact_date,'
             . ' CASE WHEN s.report_date = CURDATE() THEN s.report_uuid ELSE NULL END AS report_uuid,'
             . ' CASE WHEN s.report_date = CURDATE() THEN r.admin_uuid ELSE NULL END AS report_admin_uuid,'
             . ' CASE WHEN s.report_date = CURDATE() THEN s.charge_comment ELSE NULL END AS charge_comment,'
             . ' s.chat_user_uuid'
             . ' FROM mst_user_admin m1'
             . ' JOIN mst_user m2 ON m1.user_uuid = m2.user_uuid'
             . ' LEFT JOIN tbl_user_status_summary s ON s.user_uuid = m2.user_uuid'
             . ' LEFT JOIN tbl_report r ON r.report_uuid = s.report_uuid AND r.delete_flg = 0'
             . ' WHERE m2.group_uuid = :group_uuid AND m1.admin_uuid = :admin_uuid AND m2.delete_flg = :delete_flg'
             . ' ORDER BY m2.work_style_div ASC, m2.user_id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'group_uuid' => $groupUuid,
            'admin_uuid' => $adminUuid,
            'delete_flg' => $deleteFlag,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUserByUuid(string $userUuid): ?array
    {
        if ($userUuid === '') {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM mst_user WHERE user_uuid = :user_uuid LIMIT 1');
        $stmt->execute(['user_uuid' => $userUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findAssignableAdmins(string $groupUuid, string $excludeAdminUuid): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT admin_uuid, admin_name FROM mst_admin'
            . ' WHERE group_uuid = :group_uuid AND admin_div >= 2 AND delete_flg = 0 AND admin_uuid <> :exclude_admin_uuid'
            . ' ORDER BY admin_name'
        );
        $stmt->execute(['group_uuid' => $groupUuid, 'exclude_admin_uuid' => $excludeAdminUuid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAssignedAdminUuids(string $userUuid): array
    {
        $stmt = $this->pdo->prepare('SELECT admin_uuid FROM mst_user_admin WHERE user_uuid = :user_uuid');
        $stmt->execute(['user_uuid' => $userUuid]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findUserUuidByUserId(string $userId): string
    {
        if ($userId === '') {
            return '';
        }
        $stmt = $this->pdo->prepare('SELECT user_uuid FROM mst_user WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $value = $stmt->fetchColumn();
        return $value === false ? '' : (string) $value;
    }

    public function countActiveUsers(string $groupUuid): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM mst_user WHERE group_uuid = :group_uuid AND user_div = 1 AND delete_flg <> 9'
        );
        $stmt->execute(['group_uuid' => $groupUuid]);
        return (int) $stmt->fetchColumn();
    }

    public function userIdExists(string $userId, ?string $excludeUserUuid = null): bool
    {
        $sql    = 'SELECT 1 FROM mst_user WHERE user_id = :user_id';
        $params = ['user_id' => $userId];
        if ($excludeUserUuid !== null && $excludeUserUuid !== '') {
            $sql .= ' AND user_uuid <> :exclude_user_uuid';
            $params['exclude_user_uuid'] = $excludeUserUuid;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    public function saveUser(array $user, array $adminUuids, string $actorUuid): bool
    {
        $this->pdo->beginTransaction();
        try {
            $adminUuids = array_values(array_unique(array_filter(
                array_map('strval', array_merge($adminUuids, [$actorUuid]))
            )));
            $userUuid = (string) ($user['user_uuid'] ?? '');
            if ($userUuid === '') {
                $userUuid = $this->generateUuid();
                $user['user_uuid'] = $userUuid;
                $this->insertUser($user, $actorUuid);
            } else {
                if ($this->findUserByUuid($userUuid) === null) {
                    throw new \RuntimeException('User not found.');
                }
                $this->updateUser($user, $actorUuid);
            }
            foreach ($adminUuids as $adminUuid) {
                if (!$this->adminExists($adminUuid)) {
                    throw new \RuntimeException('Admin not found.');
                }
            }
            $this->syncUserAdmins($userUuid, $adminUuids, $actorUuid);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function refreshActiveByGroup(string $groupUuid): int
    {
        if ($groupUuid === '') {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            'SELECT user_uuid FROM mst_user WHERE group_uuid = :group_uuid AND delete_flg = 0'
        );
        $stmt->execute(['group_uuid' => $groupUuid]);
        $count = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userUuid) {
            if ($this->refreshUserStatusSummary((string) $userUuid)) {
                $count++;
            }
        }
        return $count;
    }

    private function syncUserAdmins(string $userUuid, array $adminUuids, string $actorUuid): void
    {
        $stmt = $this->pdo->prepare('SELECT admin_uuid FROM mst_user_admin WHERE user_uuid = :user_uuid');
        $stmt->execute(['user_uuid' => $userUuid]);
        $currentAdminUuids = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $inserts = array_values(array_diff($adminUuids, $currentAdminUuids));
        $deletes = array_values(array_diff($currentAdminUuids, $adminUuids));

        if ($inserts !== []) {
            $insertStmt = $this->pdo->prepare(
                'INSERT INTO mst_user_admin (user_admin_uuid, user_uuid, admin_uuid, insert_date, insert_uuid)'
                . ' VALUES (:user_admin_uuid, :user_uuid, :admin_uuid, NOW(), :actor_uuid)'
            );
            foreach ($inserts as $adminUuid) {
                $insertStmt->execute([
                    'user_admin_uuid' => $this->generateUuid(),
                    'user_uuid'       => $userUuid,
                    'admin_uuid'      => $adminUuid,
                    'actor_uuid'      => $actorUuid,
                ]);
            }
        }

        if ($deletes !== []) {
            $deleteStmt = $this->pdo->prepare(
                'DELETE FROM mst_user_admin WHERE user_uuid = :user_uuid AND admin_uuid = :admin_uuid'
            );
            foreach ($deletes as $adminUuid) {
                $deleteStmt->execute(['user_uuid' => $userUuid, 'admin_uuid' => $adminUuid]);
            }
        }
    }

    private function insertUser(array $user, string $actorUuid): void
    {
        $sql = 'INSERT INTO mst_user ('
            . ' user_uuid, group_uuid, user_id, user_password, user_div, work_style_div, delete_flg,'
            . ' visual_impairment_flg, serial_no, user_name, user_name_kana, sex_div, birthday,'
            . ' user_zip_code, user_prefecture_div, user_address, user_tel, user_email,'
            . ' volume_no, send_interval, keyboard_interval, mouse_interval, login_uuid, remark,'
            . ' insert_date, insert_uuid, update_date, update_uuid'
            . ' ) VALUES ('
            . ' :user_uuid, :group_uuid, :user_id, :user_password, :user_div, :work_style_div, :delete_flg,'
            . ' :visual_impairment_flg, :serial_no, :user_name, :user_name_kana, :sex_div, :birthday,'
            . ' :user_zip_code, :user_prefecture_div, :user_address, :user_tel, :user_email,'
            . ' :volume_no, :send_interval, :keyboard_interval, :mouse_interval, :login_uuid, :remark,'
            . ' NOW(), :insert_uuid, NOW(), :update_uuid'
            . ' )';
        $params = $this->normalizeUserParams($user, $actorUuid);
        unset($params['actor_uuid']);
        $params['insert_uuid'] = $actorUuid;
        $params['update_uuid'] = $actorUuid;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    private function updateUser(array $user, string $actorUuid): void
    {
        $sql = 'UPDATE mst_user SET'
            . ' group_uuid = :group_uuid, user_id = :user_id, user_password = :user_password,'
            . ' user_div = :user_div, work_style_div = :work_style_div, delete_flg = :delete_flg,'
            . ' visual_impairment_flg = :visual_impairment_flg, serial_no = :serial_no,'
            . ' user_name = :user_name, user_name_kana = :user_name_kana, sex_div = :sex_div,'
            . ' birthday = :birthday, user_zip_code = :user_zip_code,'
            . ' user_prefecture_div = :user_prefecture_div, user_address = :user_address,'
            . ' user_tel = :user_tel, user_email = :user_email, volume_no = :volume_no,'
            . ' send_interval = :send_interval, keyboard_interval = :keyboard_interval,'
            . ' mouse_interval = :mouse_interval, login_uuid = :login_uuid, remark = :remark,'
            . ' update_date = NOW(), update_uuid = :actor_uuid'
            . ' WHERE user_uuid = :user_uuid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->normalizeUserParams($user, $actorUuid));
    }

    private function normalizeUserParams(array $user, string $actorUuid): array
    {
        return [
            'user_uuid'              => (string) ($user['user_uuid'] ?? ''),
            'group_uuid'             => (string) ($user['group_uuid'] ?? ''),
            'user_id'                => (string) ($user['user_id'] ?? ''),
            'user_password'          => (string) ($user['user_password'] ?? ''),
            'user_div'               => (int)    ($user['user_div'] ?? 0),
            'work_style_div'         => (int)    ($user['work_style_div'] ?? 0),
            'delete_flg'             => (int)    ($user['delete_flg'] ?? 0),
            'visual_impairment_flg'  => (int)    ($user['visual_impairment_flg'] ?? 0),
            'serial_no'              => (string) ($user['serial_no'] ?? ''),
            'user_name'              => (string) ($user['user_name'] ?? ''),
            'user_name_kana'         => (string) ($user['user_name_kana'] ?? ''),
            'sex_div'                => (int)    ($user['sex_div'] ?? 0),
            'birthday'               => (string) ($user['birthday'] ?? ''),
            'user_zip_code'          => (string) ($user['user_zip_code'] ?? ''),
            'user_prefecture_div'    => (int)    ($user['user_prefecture_div'] ?? 0),
            'user_address'           => (string) ($user['user_address'] ?? ''),
            'user_tel'               => (string) ($user['user_tel'] ?? ''),
            'user_email'             => (string) ($user['user_email'] ?? ''),
            'volume_no'              => (string) ($user['volume_no'] ?? ''),
            'send_interval'          => (int)    ($user['send_interval'] ?? 30),
            'keyboard_interval'      => (int)    ($user['keyboard_interval'] ?? 30),
            'mouse_interval'         => (int)    ($user['mouse_interval'] ?? 30),
            'login_uuid'             => (string) ($user['login_uuid'] ?? ''),
            'remark'                 => (string) ($user['remark'] ?? ''),
            'actor_uuid'             => $actorUuid,
        ];
    }
}
