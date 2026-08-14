<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TrainingRepository extends AbstractRepository
{
    public function findUserByCredentials(string $userId, string $userPassword): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM mst_user WHERE user_id = :user_id AND user_password = :user_password AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'user_password' => $userPassword]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findUserByLoginToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT * FROM mst_user'
            . ' WHERE (login_uuid = :token'
            . '   OR (prev_login_uuid = :prev_token AND prev_login_uuid_expires_at > NOW()))'
            . ' AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['token' => $token, 'prev_token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findGroupNotifySettings(string $groupUuid): ?array
    {
        if ($groupUuid === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT group_name, group_email, notify_slack_webhook_url'
            . ' FROM mst_group WHERE group_uuid = :group_uuid AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['group_uuid' => $groupUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function updateUserLogin(array $params): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mst_user SET'
            . ' prev_login_uuid = CASE WHEN :login_uuid = \'\' THEN NULL ELSE login_uuid END,'
            . ' prev_login_uuid_expires_at = CASE WHEN :login_uuid2 = \'\' THEN NULL ELSE DATE_ADD(NOW(), INTERVAL 5 MINUTE) END,'
            . ' login_uuid = :login_uuid3,'
            . ' volume_no = COALESCE(:volume_no, volume_no),'
            . ' update_date = NOW()'
            . ' WHERE user_uuid = :user_uuid'
        );
        return $stmt->execute([
            'login_uuid'  => $params['login_uuid'],
            'login_uuid2' => $params['login_uuid'],
            'login_uuid3' => $params['login_uuid'],
            'volume_no'   => $params['volume_no'],
            'user_uuid'   => $params['user_uuid'],
        ]);
    }

    public function insertContact(array $contact): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_contact ('
            . ' contact_uuid, contact_div, contact_date, confirm_div, insert_uuid, delete_flg, insert_date, update_date'
            . ' ) VALUES ('
            . ' :contact_uuid, :contact_div, :contact_date, 1, :insert_uuid, 0, NOW(), NOW()'
            . ' )'
        );
        return $stmt->execute([
            'contact_uuid' => $contact['contact_uuid'],
            'contact_div'  => $contact['contact_div'],
            'contact_date' => $contact['contact_date'],
            'insert_uuid'  => $contact['insert_uuid'],
        ]);
    }

    public function insertSend(array $send): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_send ('
            . ' send_uuid, send_div, send_date, hook_div, insert_date, insert_uuid'
            . ' ) VALUES ('
            . ' :send_uuid, :send_div, :send_date, :hook_div, NOW(), :insert_uuid'
            . ' )'
        );
        return $stmt->execute([
            'send_uuid'  => $send['send_uuid'],
            'send_div'   => $send['send_div'],
            'send_date'  => $send['send_date'],
            'hook_div'   => $send['hook_div'],
            'insert_uuid' => $send['insert_uuid'],
        ]);
    }

    public function insertChat(array $chat): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_chat ('
            . ' chat_uuid, user_uuid, chat_text, user_chat_div, admin_chat_div, insert_uuid, delete_flg, insert_date, update_date'
            . ' ) VALUES ('
            . ' :chat_uuid, :user_uuid, :chat_text, :user_chat_div, :admin_chat_div, :insert_uuid, 0, NOW(), NOW()'
            . ' )'
        );
        return $stmt->execute([
            'chat_uuid'      => $chat['chat_uuid'],
            'user_uuid'      => $chat['user_uuid'],
            'chat_text'      => $chat['chat_text'],
            'user_chat_div'  => $chat['user_chat_div'],
            'admin_chat_div' => $chat['admin_chat_div'],
            'insert_uuid'    => $chat['insert_uuid'],
        ]);
    }

    public function findNewChats(string $userUuid): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.user_name, a.admin_name'
            . ' FROM tbl_chat c'
            . ' LEFT JOIN mst_user u ON c.insert_uuid = u.user_uuid'
            . ' LEFT JOIN mst_admin a ON c.insert_uuid = a.admin_uuid'
            . ' WHERE c.user_uuid = :user_uuid AND c.user_chat_div = 1'
            . ' ORDER BY c.insert_date, c.chat_uuid'
        );
        $stmt->execute(['user_uuid' => $userUuid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markUserChatsRead(array $chatUuids): void
    {
        if ($chatUuids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($chatUuids), '?'));
        $sql  = 'UPDATE tbl_chat SET user_chat_div = 2, update_date = NOW() WHERE chat_uuid IN (' . $placeholders . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($chatUuids));
    }
}
