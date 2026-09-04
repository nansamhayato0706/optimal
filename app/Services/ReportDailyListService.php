<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;

final class ReportDailyListService
{
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildPageData(string $groupUuid, string $adminUuid, string $date): array
    {
        return [
            'date' => $date,
            'rows' => $this->userRepository->findReportsByDate($groupUuid, $adminUuid, $date),
        ];
    }
}
