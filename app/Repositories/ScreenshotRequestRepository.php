<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ScreenshotRequestRepository extends AbstractRepository
{
    public const STATUS_PENDING = 1;
    public const STATUS_DONE    = 2;
    public const STATUS_FAILED  = 3;

    public function findPendingForUser(string $userUuid): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM tbl_screenshot_request'
            . ' WHERE user_uuid = :user_uuid AND status_div = :status_div AND delete_flg = 0'
            . ' ORDER BY requested_date DESC LIMIT 1'
        );
        $stmt->execute(['user_uuid' => $userUuid, 'status_div' => self::STATUS_PENDING]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findHistoryForUser(string $userUuid, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sr.*, a.admin_name'
            . ' FROM tbl_screenshot_request sr'
            . ' LEFT JOIN mst_admin a ON sr.insert_uuid = a.admin_uuid'
            . ' WHERE sr.user_uuid = :user_uuid AND sr.delete_flg = 0'
            . ' ORDER BY sr.requested_date DESC'
            . ' LIMIT ' . max(1, $limit)
        );
        $stmt->execute(['user_uuid' => $userUuid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(string $requestUuid): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM tbl_screenshot_request WHERE request_uuid = :request_uuid AND delete_flg = 0 LIMIT 1'
        );
        $stmt->execute(['request_uuid' => $requestUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function create(string $requestUuid, string $userUuid, string $adminUuid): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_screenshot_request ('
            . ' request_uuid, user_uuid, status_div, requested_date, insert_date, insert_uuid, delete_flg'
            . ' ) VALUES ('
            . ' :request_uuid, :user_uuid, :status_div, NOW(), NOW(), :insert_uuid, 0'
            . ' )'
        );
        return $stmt->execute([
            'request_uuid' => $requestUuid,
            'user_uuid'    => $userUuid,
            'status_div'   => self::STATUS_PENDING,
            'insert_uuid'  => $adminUuid,
        ]);
    }

    public function markDone(string $requestUuid, string $imagePath): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tbl_screenshot_request SET'
            . ' status_div = :status_div, image_path = :image_path, completed_date = NOW(), update_date = NOW()'
            . ' WHERE request_uuid = :request_uuid AND status_div = :pending_status_div'
        );
        $stmt->execute([
            'status_div'   => self::STATUS_DONE,
            'image_path'   => $imagePath,
            'request_uuid' => $requestUuid,
            'pending_status_div' => self::STATUS_PENDING,
        ]);
        return $stmt->rowCount() === 1;
    }

    public function markFailed(string $requestUuid): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tbl_screenshot_request SET status_div = :status_div, completed_date = NOW(), update_date = NOW()'
            . ' WHERE request_uuid = :request_uuid AND status_div = :pending_status_div'
        );
        $stmt->execute([
            'status_div'   => self::STATUS_FAILED,
            'request_uuid' => $requestUuid,
            'pending_status_div' => self::STATUS_PENDING,
        ]);
        return $stmt->rowCount() === 1;
    }

    public function markDeleted(string $requestUuid, string $adminUuid): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tbl_screenshot_request SET delete_flg = 1, update_date = NOW(), update_uuid = :update_uuid'
            . ' WHERE request_uuid = :request_uuid AND delete_flg = 0'
        );
        $stmt->execute([
            'request_uuid' => $requestUuid,
            'update_uuid'  => $adminUuid,
        ]);
        return $stmt->rowCount() === 1;
    }
}
