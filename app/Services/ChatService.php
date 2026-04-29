<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChatRepository;
use App\Repositories\UserStatusSummaryRepository;

final class ChatService
{
    private $chatRepository;
    private $userStatusSummaryRepository;

    public function __construct(ChatRepository $chatRepository, UserStatusSummaryRepository $userStatusSummaryRepository)
    {
        $this->chatRepository = $chatRepository;
        $this->userStatusSummaryRepository = $userStatusSummaryRepository;
    }

    public function buildPageData(string $userUuid, ?string $insertDate, bool $history): array
    {
        $baseDate = $this->resolveBaseDate($insertDate);
        $queryDate = $history
            ? date('Y-m-d 00:00:00', strtotime('-10 Year ' . $baseDate))
            : $baseDate;

        $messages = $this->chatRepository->findMessages($userUuid, $queryDate);
        $unreadChatUuids = [];
        foreach ($messages as $message) {
            if ((int) ($message['admin_chat_div'] ?? 0) === 1) {
                $unreadChatUuids[] = (string) $message['chat_uuid'];
            }
        }

        if ($this->chatRepository->markAdminMessagesRead($unreadChatUuids)) {
            $this->userStatusSummaryRepository->refreshUserStatusSummary($userUuid);
        }

        return [
            'date' => $queryDate,
            'user_name' => $this->chatRepository->findUserName($userUuid),
            'chat' => $messages,
        ];
    }

    public function sendMessage(string $userUuid, string $chatText, string $adminUuid = ''): array
    {
        $text = trim($chatText);
        if ($text === '') {
            return ['success' => false, 'error' => 'メッセージは必須です。'];
        }
        if (mb_strlen($text) > 255) {
            return ['success' => false, 'error' => '255文字以内で入力してください。'];
        }

        $result = $this->chatRepository->insertAdminMessage($userUuid, $text, $adminUuid);
        if ($result) {
            $this->userStatusSummaryRepository->refreshUserStatusSummary($userUuid);
        }
        return ['success' => $result, 'error' => $result ? '' : '送信に失敗しました。'];
    }

    private function resolveBaseDate(?string $insertDate): string
    {
        if ($insertDate !== null && trim($insertDate) !== '') {
            return trim($insertDate);
        }
        $today = date('Y-m-d 00:00:00');
        return date('Y-m-d 00:00:00', strtotime('-1 day ' . $today));
    }
}
