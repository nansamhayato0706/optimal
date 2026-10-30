<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;

final class ChatMonthlySummaryService
{
    private const MIN_YEAR = 2016;
    private const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * 指定月の「ユーザー × 日」のチャット送受信回数を組み立てる。
     * 赤くする日（state=warn）は、勤務した日で送+受が基準未満の日（未来日は除く）。
     */
    public function buildPageData(string $adminUuid, string $deleteFlag, string $month): array
    {
        list($year, $mon) = $this->parseMonth($month);
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $mon, 1, $year));
        $monthStart = sprintf('%04d-%02d-01 00:00:00', $year, $mon);
        $monthEnd = date('Y-m-d H:i:s', mktime(0, 0, 0, $mon + 1, 1, $year));

        $today = date('Y-m-d');
        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $timestamp = mktime(0, 0, 0, $mon, $d, $year);
            $dow = (int) date('w', $timestamp);
            $date = date('Y-m-d', $timestamp);
            $days[] = [
                'day'       => $d,
                'weekday'   => self::WEEKDAYS[$dow],
                'dow'       => $dow,
                'is_today'  => $date === $today,
                'is_future' => $date > $today,
            ];
        }

        $users = $this->userRepository->findUsersForChatSummary($adminUuid, $deleteFlag);
        $counts = $this->userRepository->findMonthlyChatCounts($adminUuid, $deleteFlag, $monthStart, $monthEnd);
        $worked = $this->userRepository->findMonthlyWorkedDays($adminUuid, $deleteFlag, $monthStart, $monthEnd);

        $rows = [];
        foreach ($users as $user) {
            $uuid = (string) $user['user_uuid'];
            $cells = [];
            $totalSend = 0;
            $totalRecv = 0;
            $achieved = 0;
            $missed = 0;
            foreach ($days as $day) {
                $d = $day['day'];
                $send = isset($counts[$uuid][$d]) ? $counts[$uuid][$d]['send'] : 0;
                $recv = isset($counts[$uuid][$d]) ? $counts[$uuid][$d]['recv'] : 0;
                $isWorked = isset($worked[$uuid][$d]);
                $isWarn = $isWorked && !$day['is_future'] && ($send + $recv) < UserListService::CHAT_MIN_EXCHANGES;
                $cells[] = [
                    'send'   => $send,
                    'recv'   => $recv,
                    'worked' => $isWorked,
                    'state'  => $isWarn ? 'warn' : '',
                ];
                if ($isWorked && !$day['is_future']) {
                    if ($isWarn) {
                        $missed++;
                    } else {
                        $achieved++;
                    }
                }
                $totalSend += $send;
                $totalRecv += $recv;
            }
            $rows[] = [
                'user_uuid'  => $uuid,
                'user_id'    => (string) $user['user_id'],
                'user_name'  => (string) $user['user_name'],
                'cells'      => $cells,
                'total_send' => $totalSend,
                'total_recv' => $totalRecv,
                'achieved'   => $achieved,
                'missed'     => $missed,
            ];
        }

        return [
            'month'       => sprintf('%04d-%02d', $year, $mon),
            'month_label' => sprintf('%d年%d月', $year, $mon),
            'days'        => $days,
            'rows'        => $rows,
        ];
    }

    /**
     * YYYY-MM を検証して [年, 月] にする。不正な値は当月に倒す。
     */
    private function parseMonth(string $month): array
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $month, $m) === 1) {
            $year = (int) $m[1];
            $mon = (int) $m[2];
            if ($mon >= 1 && $mon <= 12 && $year >= self::MIN_YEAR && $year <= (int) date('Y') + 1) {
                return [$year, $mon];
            }
        }
        return [(int) date('Y'), (int) date('n')];
    }
}
