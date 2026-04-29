<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function adminExists(string $adminUuid): bool;
    public function userAssignedToAdmin(string $userUuid, string $adminUuid): bool;
    public function findUsers(string $groupUuid, string $adminUuid, string $deleteFlag): array;
    public function findUserStatuses(string $groupUuid, string $adminUuid, string $deleteFlag): array;
    public function findUserByUuid(string $userUuid): ?array;
    public function findDivMap(): array;
    public function findAdminBySessionUuid(string $sessionUuid): ?array;
    public function findAssignableAdmins(string $groupUuid, string $excludeAdminUuid): array;
    public function findAssignedAdminUuids(string $userUuid): array;
    public function findUserUuidByUserId(string $userId): string;
    public function countActiveUsers(string $groupUuid): int;
    public function userIdExists(string $userId, ?string $excludeUserUuid = null): bool;
    public function saveUser(array $user, array $adminUuids, string $actorUuid): bool;
    public function refreshActiveByGroup(string $groupUuid): int;
}
