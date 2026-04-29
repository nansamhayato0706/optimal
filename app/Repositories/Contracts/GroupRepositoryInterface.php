<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface GroupRepositoryInterface
{
    public function findGroups(): array;
    public function findGroupByUuid(string $groupUuid): ?array;
    public function findDivMap(): array;
    public function saveGroup(array $group, string $actorUuid): bool;
}
