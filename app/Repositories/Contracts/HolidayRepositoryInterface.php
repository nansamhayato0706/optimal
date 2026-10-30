<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface HolidayRepositoryInterface
{
    /**
     * 指定期間の祝日を返す。
     *
     * @return array<string, string> 'YYYY-MM-DD' => 祝日名
     */
    public function findHolidays(string $fromDate, string $toDateExclusive): array;
}
