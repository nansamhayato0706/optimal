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
            . ' WHERE c.user_uuid = :user_uuid AND c.insert_date >= :insert_date AND c.delete_flg = 0'
            . ' ORDER BY c.insert_date DESC, c.chat_uuid DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_uuid' => $userUuid,
            'insert_date' => $insertDate,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findMessageById(string $chatUuid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tbl_chat WHERE chat_uuid = :chat_uuid AND delete_flg = 0 LIMIT 1');
        $stmt->execute(['chat_uuid' => $chatUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function deleteMessage(string $chatUuid): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tbl_chat SET delete_flg = 1, update_date = NOW() WHERE chat_uuid = :chat_uuid AND delete_flg = 0'
        );
        $stmt->execute(['chat_uuid' => $chatUuid]);
        return $stmt->rowCount() > 0;
    }

    public function updateMessageText(string $chatUuid, string $chatText): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tbl_chat SET chat_text = :chat_text, update_date = NOW() WHERE chat_uuid = :chat_uuid AND delete_flg = 0'
        );
        $stmt->execute(['chat_text' => $chatText, 'chat_uuid' => $chatUuid]);
        return $stmt->rowCount() > 0;
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
