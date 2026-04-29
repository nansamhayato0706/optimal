<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ContactRepository extends AbstractRepository
{
    public function findContactByUuid(string $contactUuid): ?array
    {
        if ($contactUuid === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM tbl_contact WHERE contact_uuid = :contact_uuid AND delete_flg = 0 LIMIT 1');
        $stmt->execute(['contact_uuid' => $contactUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function updateContact(array $contact, string $actorUuid): bool
    {
        $sql = 'UPDATE tbl_contact SET'
            . ' confirm_div = :confirm_div, confirm_date = :confirm_date, comment = :comment,'
            . ' comment_date = :comment_date, comment_admin_uuid = :comment_admin_uuid,'
            . ' update_date = NOW(), update_uuid = :actor_uuid'
            . ' WHERE contact_uuid = :contact_uuid';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'confirm_div' => (int) $contact['confirm_div'],
            'confirm_date' => $this->nullableString($contact['confirm_date'] ?? null),
            'comment' => (string) ($contact['comment'] ?? ''),
            'comment_date' => $this->nullableString($contact['comment_date'] ?? null),
            'comment_admin_uuid' => $this->nullableString($contact['comment_admin_uuid'] ?? null),
            'actor_uuid' => $actorUuid,
            'contact_uuid' => (string) $contact['contact_uuid'],
        ]);
    }

    private function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;
        return $value === '' ? null : $value;
    }
}
