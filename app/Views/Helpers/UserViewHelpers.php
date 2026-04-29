<?php

declare(strict_types=1);

namespace App\Views\Helpers;

final class UserViewHelpers
{
    public static function contactClass(array $user): string
    {
        $confirmDiv = (int) ($user['confirm_div'] ?? 0);
        $contactDiv = (int) ($user['contact_div'] ?? 0);
        if ($confirmDiv === 1 || $confirmDiv === 2) {
            return 'user_contact_' . $contactDiv;
        }
        return 'user_contact_f_' . $contactDiv;
    }

    public static function contactHtml(array $user, array $divMap): string
    {
        $confirmDiv = (int) ($user['confirm_div'] ?? 0);
        $contactDiv = (int) ($user['contact_div'] ?? 0);
        $dateStr = '';
        if (!empty($user['contact_date'])) {
            $ts = strtotime((string) $user['contact_date']);
            $dateStr = $ts === false ? '' : date('y/m/d H:i', $ts);
        }
        $contactLabel = htmlspecialchars((string) ($divMap['contact'][$contactDiv] ?? ''), ENT_QUOTES, 'UTF-8');
        $html = htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') . ' ' . $contactLabel;
        if ($confirmDiv === 1 || $confirmDiv === 2) {
            $contactUuid = htmlspecialchars((string) ($user['contact_uuid'] ?? ''), ENT_QUOTES, 'UTF-8');
            $html .= ' <a class="h_link user-status-link" href="contact.php?i=' . $contactUuid . '">確認</a>';
            return $html;
        }
        $confirmLabel = htmlspecialchars((string) ($divMap['confirm'][$confirmDiv] ?? ''), ENT_QUOTES, 'UTF-8');
        return $html . ' ' . $confirmLabel;
    }

    public static function reportClass(array $user): string
    {
        if (empty($user['report_uuid'])) {
            return '';
        }
        return trim((string) ($user['report_admin_uuid'] ?? '')) === '' ? 'user_report' : '';
    }

    public static function reportHtml(array $user): string
    {
        $userUuid   = htmlspecialchars((string) $user['user_uuid'], ENT_QUOTES, 'UTF-8');
        $reportUuid = htmlspecialchars((string) ($user['report_uuid'] ?? ''), ENT_QUOTES, 'UTF-8');
        if (empty($user['report_uuid'])) {
            return '<a href="report.php?i=' . $userUuid . '">一覧</a>';
        }
        if (trim((string) ($user['report_admin_uuid'] ?? '')) === '') {
            return '<a href="report_edit.php?i=' . $reportUuid . '">編集</a>';
        }
        return '<a href="report_detail.php?i=' . $reportUuid . '">確認</a>';
    }
}
