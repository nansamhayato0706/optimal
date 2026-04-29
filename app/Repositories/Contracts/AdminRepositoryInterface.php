<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface AdminRepositoryInterface
{
    public function findAdminByCredentials(string $adminId, string $adminPassword): ?array;
    public function findAdminByUuid(string $adminUuid): ?array;
    public function groupExists(string $groupUuid): bool;
    public function findAdmins(string $groupUuid): array;
    public function adminIdExists(string $adminId, ?string $excludeAdminUuid = null): bool;
    public function saveAdmin(array $admin, string $actorUuid): bool;
}
