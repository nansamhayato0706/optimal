<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FirstRepository;

final class FirstService
{
    private $firstRepository;

    public function __construct(FirstRepository $firstRepository)
    {
        $this->firstRepository = $firstRepository;
    }

    public function bootstrap(string $token): array
    {
        $user = $this->firstRepository->findUserByLoginToken($token);
        if ($user === null) {
            return ['error' => 'token error'];
        }
        $group = $this->firstRepository->findGroupSummary((string) $user['group_uuid']);
        if ($group === null) {
            return ['error' => 'token error'];
        }
        return [
            'group'  => (string) $group['group_name'],
            'notice' => (string) $group['notification'],
            'link'   => $this->firstRepository->findClientLinks((string) $user['group_uuid']),
        ];
    }
}
