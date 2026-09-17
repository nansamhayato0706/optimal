<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChatRepository;
use App\Repositories\UserStatusSummaryRepository;
use App\Support\ChatFileStorage;
use App\Support\ChatViewHelpers;

final class ChatService
{
    private $chatRepository;
    private $userStatusSummaryRepository;
    private $chatFileStorage;

    public function __construct(
        ChatRepository $chatRepository,
        UserStatusSummaryRepository $userStatusSummaryRepository,
        ChatFileStorage $chatFileStorage
    ) {
        $this->chatRepository = $chatRepository;
        $this->userStatusSummaryRepository = $userStatusSummaryRepository;
        $this->chatFileStorage = $chatFileStorage;
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

    public function sendMessage(string $userUuid, string $chatText, string $adminUuid = '', ?array $file = null): array
    {
        $hasFile = $file !== null
            && isset($file['tmp_name'])
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($hasFile) {
            $stored = $this->chatFileStorage->store($file, $userUuid);
            if ($stored === null) {
                return ['success' => false, 'error' => 'ファイルの送信に失敗しました。対応形式・サイズ（10MBまで）をご確認ください。'];
            }
            $text = $this->chatFileStorage->buildMessageText($stored);
        } else {
            $text = trim($chatText);
            if ($text === '') {
                return ['success' => false, 'error' => 'メッセージは必須です。'];
            }
            if (mb_strlen($text) > 255) {
                return ['success' => false, 'error' => '255文字以内で入力してください。'];
            }
        }

        $result = $this->chatRepository->insertAdminMessage($userUuid, $text, $adminUuid);
        if ($result) {
            $this->userStatusSummaryRepository->refreshUserStatusSummary($userUuid);
        }
        return ['success' => $result, 'error' => $result ? '' : '送信に失敗しました。'];
    }

    public function deleteMessage(string $userUuid, string $chatUuid): array
    {
        $message = $this->chatRepository->findMessageById($chatUuid);
        if ($message === null || (string) ($message['user_uuid'] ?? '') !== $userUuid) {
            return ['success' => false, 'error' => 'メッセージが見つかりません。'];
        }

        $deleted = $this->chatRepository->deleteMessage($chatUuid);
        if ($deleted) {
            $this->chatFileStorage->deleteIfChatFileUrl((string) ($message['chat_text'] ?? ''), $userUuid);
            $this->userStatusSummaryRepository->refreshUserStatusSummary($userUuid);
        }
        return ['success' => $deleted, 'error' => $deleted ? '' : '削除に失敗しました。'];
    }

    public function editMessage(string $userUuid, string $chatUuid, string $chatText, string $adminUuid): array
    {
        $message = $this->chatRepository->findMessageById($chatUuid);
        if ($message === null || (string) ($message['user_uuid'] ?? '') !== $userUuid) {
            return ['success' => false, 'error' => 'メッセージが見つかりません。'];
        }

        if ($adminUuid === '' || (string) ($message['insert_uuid'] ?? '') !== $adminUuid) {
            return ['success' => false, 'error' => '自分が送信したメッセージのみ編集できます。'];
        }

        if (ChatViewHelpers::isFileOnlyMessage($message)) {
            return ['success' => false, 'error' => 'ファイル送信メッセージは編集できません。'];
        }

        $text = trim($chatText);
        if ($text === '') {
            return ['success' => false, 'error' => 'メッセージは必須です。'];
        }
        if (mb_strlen($text) > 255) {
            return ['success' => false, 'error' => '255文字以内で入力してください。'];
        }

        $updated = $this->chatRepository->updateMessageText($chatUuid, $text);
        return ['success' => $updated, 'error' => $updated ? '' : '更新に失敗しました。'];
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
