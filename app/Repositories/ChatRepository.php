<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ChatRepository extends AbstractRepository
{
    public function findUserName(string $userUuid): string
    {
        if ($userUuid === '') {
            return '';
        }
        $stmt = $this->pdo->prepare('SELECT user_name FROM mst_user WHERE user_uuid = :user_uuid LIMIT 1');
        $stmt->execute(['user_uuid' => $userUuid]);
        $value = $stmt->fetchColumn();
        return $value === false ? '' : (string) $value;
    }

    public function findMessages(string $userUuid, string $insertDate): array
    {
        $sql = 'SELECT c.*, u.user_name, a.admin_name'
            . ' FROM tbl_chat c'
            . ' LEFT JOIN mst_user u ON c.insert_uuid = u.user_uuid'
            . ' LEFT JOIN mst_admin a ON c.insert_uuid = a.admin_uuid'
            . ' WHERE c.user_uuid = :user_uuid AND c.insert_date >= :insert_date'
            . ' ORDER BY c.insert_date DESC, c.chat_uuid DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_uuid' => $userUuid,
            'insert_date' => $insertDate,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAdminMessagesRead(array $chatUuids): bool
    {
        $chatUuids = array_values(array_filter(array_map('strval', $chatUuids)));
        if ($chatUuids === []) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($chatUuids), '?'));
        $stmt = $this->pdo->prepare(
            'UPDATE tbl_chat SET admin_chat_div = 2, update_date = NOW() WHERE chat_uuid IN (' . $placeholders . ')'
        );
        $stmt->execute($chatUuids);
        return $stmt->rowCount() > 0;
    }

    public function insertAdminMessage(string $userUuid, string $chatText, string $adminUuid = ''): bool
    {
        $insertUuid = $adminUuid !== '' ? $adminUuid : $userUuid;

        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_chat ('
            . ' chat_uuid, user_uuid, chat_text, user_chat_div, admin_chat_div, insert_uuid, delete_flg, insert_date, update_date'
            . ' ) VALUES ('
            . ' :chat_uuid, :user_uuid, :chat_text, 1, 2, :insert_uuid, 0, NOW(), NOW()'
            . ' )'
        );
        return $stmt->execute([
            'chat_uuid' => $this->generateUuid(),
            'user_uuid' => $userUuid,
            'chat_text' => $chatText,
            'insert_uuid' => $insertUuid,
        ]);
    }
}
