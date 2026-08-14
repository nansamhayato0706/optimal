<?php

declare(strict_types=1);

namespace App\Support;

final class InquirySlackNotifier
{
    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    public function sendInquiryNotification(array $user, string $contactUuid, string $contactDate, ?array $group = null): void
    {
        $this->notify($user, $contactUuid, $contactDate, '🔴 *問い合わせが届きました*', $group);
    }

    public function sendEmergencyNotification(array $user, string $contactUuid, string $contactDate, ?array $group = null): void
    {
        $this->notify($user, $contactUuid, $contactDate, '🚨 *緊急通知が届きました* 🚨', $group);
    }

    private function notify(array $user, string $contactUuid, string $contactDate, string $header, ?array $group): void
    {
        // 事業所の Webhook を優先し、未設定なら全体設定（本部）へフォールバックする
        $url = trim((string) ($group['notify_slack_webhook_url'] ?? ''));
        if ($url === '') {
            $url = trim($this->config->slackWebhookUrl());
        }
        if ($url === '') {
            return;
        }

        $userId    = (string) ($user['user_id']   ?? '');
        $userName  = (string) ($user['user_name'] ?? '');
        $groupName = (string) ($group['group_name'] ?? '');
        $appUrl    = $this->config->appUrl();
        $link      = $appUrl !== '' ? $appUrl . '/contact.php?i=' . rawurlencode($contactUuid) : '';

        $lines = [$header];
        if ($groupName !== '') {
            $lines[] = '事業所　　: ' . $groupName;
        }
        $lines[] = '利用者名　: ' . $userName;
        $lines[] = 'ユーザーID: ' . $userId;
        $lines[] = '受信時刻　: ' . $contactDate;
        if ($link !== '') {
            $lines[] = '詳細　　　: ' . $link;
        }
        $text = implode("\n", $lines);

        $this->post($url, $text);
    }

    private function post(string $url, string $text): void
    {
        $payload = json_encode(['text' => $text]);
        if ($payload === false) {
            return;
        }

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_exec($ch);
                curl_close($ch);
            } else {
                $context = stream_context_create([
                    'http' => [
                        'method'  => 'POST',
                        'header'  => 'Content-Type: application/json',
                        'content' => $payload,
                        'timeout' => 5,
                    ],
                ]);
                @file_get_contents($url, false, $context);
            }
        } catch (\Throwable $e) {
            Logger::error('InquirySlackNotifier: post failed', ['message' => $e->getMessage()]);
        }
    }
}
