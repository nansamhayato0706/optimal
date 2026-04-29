<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class LinkRepository extends AbstractRepository
{
    public function findLinksByGroup(string $groupUuid): array
    {
        if ($groupUuid === '') {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT link_uuid, group_uuid, link_url, link_name, sort'
            . ' FROM mst_link WHERE group_uuid = :group_uuid'
            . ' ORDER BY sort, link_name'
        );
        $stmt->execute(['group_uuid' => $groupUuid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function replaceLinks(string $groupUuid, array $links, string $actorUuid): bool
    {
        $this->pdo->beginTransaction();
        try {
            $deleteStmt = $this->pdo->prepare('DELETE FROM mst_link WHERE group_uuid = :group_uuid');
            $deleteStmt->execute(['group_uuid' => $groupUuid]);

            if ($links !== []) {
                $insertStmt = $this->pdo->prepare(
                    'INSERT INTO mst_link ('
                    . ' link_uuid, group_uuid, link_url, link_name, sort, insert_date, insert_uuid'
                    . ' ) VALUES ('
                    . ' :link_uuid, :group_uuid, :link_url, :link_name, :sort, NOW(), :actor_uuid'
                    . ' )'
                );
                foreach ($links as $link) {
                    $insertStmt->execute([
                        'link_uuid' => $this->generateUuid(),
                        'group_uuid' => $groupUuid,
                        'link_url' => (string) $link['link_url'],
                        'link_name' => (string) $link['link_name'],
                        'sort' => (int) $link['sort'],
                        'actor_uuid' => $actorUuid,
                    ]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }
}
