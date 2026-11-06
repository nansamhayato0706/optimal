<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

final class ChatMonthlySummaryService
{
    private const MIN_YEAR = 2016;
    private const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

    private $userRepository;
    private $holidayRepository;

    public function __construct(UserRepositoryInterface $userRepository, HolidayRepositoryInterface $holidayRepository)
    {
        $this->userRepository = $userRepository;
        $this->holidayRepository = $holidayRepository;
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

        $holidays = $this->holidayRepository->findHolidays(substr($monthStart, 0, 10), substr($monthEnd, 0, 10));

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
                'is_holiday' => isset($holidays[$date]),
                'holiday_name' => isset($holidays[$date]) ? $holidays[$date] : '',
                // 土日・祝日は達成/未達成の集計に含めない
                'is_off'    => $dow === 0 || $dow === 6 || isset($holidays[$date]),
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
                $isWarn = $isWorked && !$day['is_future'] && !$day['is_off'] && ($send + $recv) < UserListService::CHAT_MIN_EXCHANGES;
                $cells[] = [
                    'send'   => $send,
                    'recv'   => $recv,
                    'worked' => $isWorked,
                    'state'  => $isWarn ? 'warn' : '',
                ];
                if ($isWorked && !$day['is_future'] && !$day['is_off']) {
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
     * CSV を組み立てる。type は summary（ユーザー別サマリー）/ daily（日別明細）。
     * 戻り値は ['csv' => Shift_JIS の CSV 文字列, 'file_name' => ファイル名]。
     */
    public function buildCsv(string $adminUuid, string $deleteFlag, string $month, string $type): array
    {
        $page = $this->buildPageData($adminUuid, $deleteFlag, $month);
        $ym = str_replace('-', '', $page['month']);

        if ($type === 'daily') {
            $rows = $this->buildDailyCsvRows($page);
            $fileName = 'chat_summary_' . $ym . '_daily.csv';
        } else {
            $rows = $this->buildSummaryCsvRows($page);
            $fileName = 'chat_summary_' . $ym . '_summary.csv';
        }

        return ['csv' => $this->encodeCsv($rows), 'file_name' => $fileName];
    }

    private function buildSummaryCsvRows(array $page): array
    {
        $rows = [[
            'ユーザーID', '氏名', '勤務日数', '対象日数', '達成日数', '未達成日数', '達成率(%)',
            '送信合計', '受信合計', 'やり取り合計', '勤務日あたり平均やり取り数',
        ]];

        $sum = ['worked' => 0, 'achieved' => 0, 'missed' => 0, 'send' => 0, 'recv' => 0];
        foreach ($page['rows'] as $row) {
            $workedDays = 0;
            foreach ($row['cells'] as $i => $cell) {
                $day = $page['days'][$i];
                if ($cell['worked'] && !$day['is_future']) {
                    $workedDays++;
                }
            }
            $rows[] = $this->summaryLine(
                $row['user_id'], $row['user_name'], $workedDays,
                $row['achieved'], $row['missed'], $row['total_send'], $row['total_recv']
            );
            $sum['worked'] += $workedDays;
            $sum['achieved'] += $row['achieved'];
            $sum['missed'] += $row['missed'];
            $sum['send'] += $row['total_send'];
            $sum['recv'] += $row['total_recv'];
        }

        if ($page['rows'] !== []) {
            $rows[] = $this->summaryLine('', '全体', $sum['worked'], $sum['achieved'], $sum['missed'], $sum['send'], $sum['recv']);
        }

        return $rows;
    }

    private function summaryLine(string $userId, string $name, int $workedDays, int $achieved, int $missed, int $send, int $recv): array
    {
        $target = $achieved + $missed;
        $total = $send + $recv;
        return [
            $userId,
            $name,
            $workedDays,
            $target,
            $achieved,
            $missed,
            $target > 0 ? round($achieved / $target * 100, 1) : '',
            $send,
            $recv,
            $total,
            $workedDays > 0 ? round($total / $workedDays, 1) : '',
        ];
    }

    private function buildDailyCsvRows(array $page): array
    {
        $rows = [['日付', '曜日', '祝日名', 'ユーザーID', '氏名', '勤務', '送信', '受信', '判定']];
        foreach ($page['rows'] as $row) {
            foreach ($row['cells'] as $i => $cell) {
                $day = $page['days'][$i];
                if ($cell['state'] === 'warn') {
                    $judge = '未達成';
                } elseif ($cell['worked'] && !$day['is_future'] && !$day['is_off']) {
                    $judge = '達成';
                } else {
                    $judge = '対象外';
                }
                $rows[] = [
                    sprintf('%s-%02d', $page['month'], $day['day']),
                    $day['weekday'],
                    $day['holiday_name'],
                    $row['user_id'],
                    $row['user_name'],
                    $cell['worked'] ? '○' : '',
                    $cell['send'],
                    $cell['recv'],
                    $judge,
                ];
            }
        }
        return $rows;
    }

    /**
     * Shift_JIS(CP932) の CSV 文字列にする。数式として解釈されうる文字列は先頭に ' を付ける。
     */
    private function encodeCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            $encoded = [];
            foreach ($row as $value) {
                $text = (string) $value;
                if (is_string($value) && $text !== '' && strpos('=+-@', $text[0]) !== false) {
                    $text = "'" . $text;
                }
                $encoded[] = mb_convert_encoding($text, 'SJIS-win', 'UTF-8');
            }
            fputcsv($stream, $encoded);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv === false ? '' : $csv;
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
