<?php

declare(strict_types=1);

namespace App\Support;

final class InquiryMailer
{
    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    public function sendInquiryNotification(array $user, string $contactUuid, string $contactDate, ?array $group = null): void
    {
        $this->send(
            $user,
            $contactUuid,
            $contactDate,
            '【在宅就労管理】問い合わせ通知 ',
            '在宅就労管理システムに問い合わせが届きました。',
            $group
        );
    }

    public function sendEmergencyNotification(array $user, string $contactUuid, string $contactDate, ?array $group = null): void
    {
        $this->send(
            $user,
            $contactUuid,
            $contactDate,
            '【在宅就労管理】緊急通知 ',
            '⚠ 緊急通知 ⚠ 在宅就労管理システムに緊急の連絡が届きました。',
            $group
        );
    }

    private function send(array $user, string $contactUuid, string $contactDate, string $subjectPrefix, string $bodyIntro, ?array $group): void
    {
        if (!$this->config->inquiryMailEnabled()) {
            return;
        }

        // 事業所の group_email を優先し、未設定なら全体設定（本部）へフォールバックする
        $to = trim((string) ($group['group_email'] ?? ''));
        if ($to === '') {
            $to = trim($this->config->inquiryMailTo());
        }
        $from = trim($this->config->inquiryMailFrom());
        if ($to === '' || $from === '') {
            return;
        }

        $userId    = (string) ($user['user_id']   ?? '');
        $userName  = (string) ($user['user_name'] ?? '');
        $groupName = (string) ($group['group_name'] ?? '');
        $appUrl    = $this->config->appUrl();
        $contactUrl = $appUrl !== '' ? $appUrl . '/contact.php?i=' . rawurlencode($contactUuid) : '';
        $listUrl    = $appUrl !== '' ? $appUrl . '/user.php' : '';

        $subject = $subjectPrefix . $userName;
        $bodyLines = [
            $bodyIntro,
            '',
        ];
        if ($groupName !== '') {
            $bodyLines[] = '■ 事業所　 : ' . $groupName;
        }
        $bodyLines[] = '■ 利用者名 : ' . $userName;
        $bodyLines[] = '■ ユーザーID: ' . $userId;
        $bodyLines[] = '■ 受信時刻 : ' . $contactDate;
        if ($contactUrl !== '') {
            $bodyLines[] = '';
            $bodyLines[] = '▼ 連絡詳細';
            $bodyLines[] = $contactUrl;
        }
        if ($listUrl !== '') {
            $bodyLines[] = '';
            $bodyLines[] = '▼ 利用者一覧';
            $bodyLines[] = $listUrl;
        }
        $body = implode("\r\n", $bodyLines) . "\r\n";

        try {
            $previousLang     = mb_language();
            $previousInternal = mb_internal_encoding();
            mb_language('Japanese');
            mb_internal_encoding('UTF-8');

            $headers = [
                'From: ' . $this->headerValue($from),
                'Reply-To: ' . $this->headerValue($from),
                'X-Mailer: ZaitakuKanri/InquiryMailer',
            ];
            $additionalHeaders = implode("\r\n", $headers);
            // Envelope sender (-f) は管理者がメール配送で許可している場合のみ尊重される。
            $additionalParams  = '-f' . $from;

            $sent = @mb_send_mail($to, $subject, $body, $additionalHeaders, $additionalParams);

            if (is_string($previousLang) && $previousLang !== '') {
                mb_language($previousLang);
            }
            if (is_string($previousInternal) && $previousInternal !== '') {
                mb_internal_encoding($previousInternal);
            }

            if (!$sent) {
                Logger::warning('InquiryMailer: mb_send_mail returned false', [
                    'to'   => $to,
                    'user' => $userId,
                ]);
            }
        } catch (\Throwable $e) {
            Logger::error('InquiryMailer: send failed', [
                'message' => $e->getMessage(),
                'to'      => $to,
                'user'    => $userId,
            ]);
        }
    }

    private function headerValue(string $address): string
    {
        // メールヘッダーインジェクション対策で改行を除去する。
        return preg_replace('/[\r\n]+/', '', $address) ?? '';
    }
}
