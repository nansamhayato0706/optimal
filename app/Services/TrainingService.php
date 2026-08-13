<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TrainingRepository;
use App\Repositories\UserStatusSummaryRepository;
use App\Support\InquiryMailer;
use App\Support\InquirySlackNotifier;
use App\Support\TrainingImageStorage;
use App\Support\TrainingLoginPayload;
use App\Support\TrainingPayload;
use App\Support\TrainingResponses;
use App\Support\Uuid;

final class TrainingService
{
    private $trainingRepository;
    private $summaryRepository;
    private $imageStorage;
    private $inquiryMailer;
    private $slackNotifier;

    public function __construct(
        TrainingRepository $trainingRepository,
        UserStatusSummaryRepository $summaryRepository,
        TrainingImageStorage $imageStorage,
        InquiryMailer $inquiryMailer,
        InquirySlackNotifier $slackNotifier
    ) {
        $this->trainingRepository = $trainingRepository;
        $this->summaryRepository  = $summaryRepository;
        $this->imageStorage       = $imageStorage;
        $this->inquiryMailer      = $inquiryMailer;
        $this->slackNotifier      = $slackNotifier;
    }

    public function login(TrainingLoginPayload $payload): array
    {
        if (!$payload->isComplete()) {
            return TrainingResponses::loginFailed();
        }

        $user = $this->trainingRepository->findUserByCredentials($payload->userId(), $payload->userPassword());
        if ($user === null) {
            return TrainingResponses::loginFailed();
        }
        if ((string) $user['serial_no'] !== $payload->serialNo()) {
            return TrainingResponses::loginFailed();
        }
        if ((string) $user['volume_no'] !== '' && (string) $user['volume_no'] !== $payload->volumeNo()) {
            return TrainingResponses::loginFailed();
        }

        $token   = Uuid::v4();
        $updated = $this->trainingRepository->updateUserLogin([
            'user_uuid'  => (string) $user['user_uuid'],
            'login_uuid' => $token,
            'volume_no'  => (string) $user['volume_no'] === '' ? $payload->volumeNo() : null,
        ]);
        if (!$updated) {
            return TrainingResponses::loginFailed();
        }

        $this->insertSend((string) $user['user_uuid'], 5, 0);
        return TrainingResponses::loginSuccess($token, $user);
    }

    public function handleEvent(int $eventType, array $post, array $files): array
    {
        $payload  = TrainingPayload::fromEventPost($post);
        $token    = $payload->token();

        $user = $this->trainingRepository->findUserByLoginToken($token);
        if ($user === null) {
            return TrainingResponses::eventFailed();
        }

        $userUuid = (string) $user['user_uuid'];
        $result   = TrainingResponses::eventFailed();
        $newToken = $token;

        if (!in_array($eventType, [4, 7, 8, 11, 12], true)) {
            $newToken = $eventType === 6 ? '' : Uuid::v4();
            $updated  = $this->trainingRepository->updateUserLogin([
                'user_uuid'  => $userUuid,
                'login_uuid' => $newToken,
                'volume_no'  => null,
            ]);
            if (!$updated) {
                return $result;
            }
            $result = TrainingResponses::tokenResult($newToken);
        } else {
            $result = TrainingResponses::tokenResult($token);
        }

        if ($eventType === 11 || $eventType === 12) {
            return $this->handleChatEvent($eventType, $userUuid, $payload->chatText());
        }

        if ($eventType <= 5) {
            $imageStored = $this->imageStorage->store($files, $userUuid, $eventType);
            if (($eventType === 4 || isset($files['file'])) && !$imageStored) {
                return TrainingResponses::eventFailed();
            }

            $contactUuid = Uuid::v4();
            $contactDate = date('Y-m-d H:i:s');
            $contactInserted = $this->trainingRepository->insertContact([
                'contact_uuid' => $contactUuid,
                'contact_div'  => $eventType,
                'contact_date' => $contactDate,
                'insert_uuid'  => $userUuid,
            ]);
            if (!$contactInserted) {
                return TrainingResponses::eventFailed();
            }

            $this->summaryRepository->refreshUserStatusSummary($userUuid);
            if ($eventType === 3 || $eventType === 5) {
                // 事業所ごとの通知先。未設定項目は各 Notifier 内で全体設定へフォールバックする
                $group = $this->trainingRepository->findGroupNotifySettings((string) ($user['group_uuid'] ?? ''));
                if ($eventType === 3) {
                    $this->inquiryMailer->sendInquiryNotification($user, $contactUuid, $contactDate, $group);
                    $this->slackNotifier->sendInquiryNotification($user, $contactUuid, $contactDate, $group);
                } else {
                    $this->inquiryMailer->sendEmergencyNotification($user, $contactUuid, $contactDate, $group);
                    $this->slackNotifier->sendEmergencyNotification($user, $contactUuid, $contactDate, $group);
                }
            }
        }

        $sendInserted = $this->insertSend($userUuid, $eventType, $payload->hookDiv());
        if (!$sendInserted && $eventType !== 6) {
            return TrainingResponses::eventFailed();
        }

        return $result;
    }

    private function handleChatEvent(int $eventType, string $userUuid, string $chatText): array
    {
        if ($eventType === 11 && $chatText !== '') {
            $inserted = $this->trainingRepository->insertChat([
                'chat_uuid'      => Uuid::v4(),
                'user_uuid'      => $userUuid,
                'chat_text'      => $chatText,
                'user_chat_div'  => 1,
                'admin_chat_div' => 1,
                'insert_uuid'    => $userUuid,
            ]);
            if ($inserted) {
                $this->summaryRepository->refreshUserStatusSummary($userUuid);
            }
        }

        $messages  = $this->trainingRepository->findNewChats($userUuid);
        $chatUuids = array_map(static function (array $row): string {
            return (string) $row['chat_uuid'];
        }, $messages);
        $this->trainingRepository->markUserChatsRead($chatUuids);
        return $messages;
    }

    private function insertSend(string $userUuid, int $eventType, int $hookDiv): bool
    {
        return $this->trainingRepository->insertSend([
            'send_uuid'   => Uuid::v4(),
            'send_div'    => $eventType,
            'send_date'   => date('Y-m-d H:i:s'),
            'hook_div'    => $hookDiv,
            'insert_uuid' => $userUuid,
        ]);
    }
}
