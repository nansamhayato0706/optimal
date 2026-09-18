<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ScreenshotRequestRepository;
use App\Repositories\TrainingRepository;
use App\Support\ScreenshotStorage;
use App\Support\Uuid;

final class ScreenshotService
{
    private const TIMEOUT_SECONDS = 60;

    private $screenshotRepository;
    private $trainingRepository;
    private $storage;

    public function __construct(
        ScreenshotRequestRepository $screenshotRepository,
        TrainingRepository $trainingRepository,
        ScreenshotStorage $storage
    ) {
        $this->screenshotRepository = $screenshotRepository;
        $this->trainingRepository   = $trainingRepository;
        $this->storage              = $storage;
    }

    /**
     * 管理画面からの要求作成。pending中の要求があればそれを使い回す（多重要求防止）。
     */
    public function requestCapture(string $userUuid, string $adminUuid): array
    {
        $existing = $this->screenshotRepository->findPendingForUser($userUuid);
        if ($existing !== null && !$this->isStale($existing)) {
            return ['request_uuid' => $existing['request_uuid'], 'status' => 'pending'];
        }
        if ($existing !== null) {
            $this->screenshotRepository->markFailed($existing['request_uuid']);
        }

        $requestUuid = Uuid::v4();
        if (!$this->screenshotRepository->create($requestUuid, $userUuid, $adminUuid)) {
            return ['error' => '要求の作成に失敗しました。'];
        }

        return ['request_uuid' => $requestUuid, 'status' => 'pending'];
    }

    /**
     * WPFクライアントのポーリングから呼ばれる。自分宛のpending要求があればrequest_uuidを返す。
     */
    public function checkPendingForToken(string $token): ?string
    {
        $user = $this->trainingRepository->findUserByLoginToken($token);
        if ($user === null) {
            return null;
        }

        $pending = $this->screenshotRepository->findPendingForUser((string) $user['user_uuid']);
        if ($pending === null || $this->isStale($pending)) {
            return null;
        }

        return (string) $pending['request_uuid'];
    }

    /**
     * WPFクライアントからのアップロードを受け取り保存する。
     */
    public function receiveCapture(string $requestUuid, string $token, array $files): array
    {
        $user = $this->trainingRepository->findUserByLoginToken($token);
        if ($user === null) {
            return ['error' => 'token error'];
        }

        $request = $this->screenshotRepository->find($requestUuid);
        if ($request === null
            || (string) $request['user_uuid'] !== (string) $user['user_uuid']
            || (int) $request['status_div'] !== ScreenshotRequestRepository::STATUS_PENDING
        ) {
            return ['error' => 'invalid request'];
        }

        $relativePath = $this->storage->store($files['file'] ?? [], (string) $user['user_uuid'], $requestUuid);
        if ($relativePath === null) {
            $this->screenshotRepository->markFailed($requestUuid);
            return ['error' => '保存に失敗しました。'];
        }

        $this->screenshotRepository->markDone($requestUuid, $relativePath);
        return ['result' => 'ok'];
    }

    /**
     * 管理画面のポーリング確認用。呼び出し元でuserUuidの管理範囲チェック済みであること。
     */
    public function getStatus(string $requestUuid, string $userUuid): array
    {
        $request = $this->screenshotRepository->find($requestUuid);
        if ($request === null || (string) $request['user_uuid'] !== $userUuid) {
            return ['error' => 'not found'];
        }

        if ((int) $request['status_div'] === ScreenshotRequestRepository::STATUS_PENDING && $this->isStale($request)) {
            $this->screenshotRepository->markFailed($requestUuid);
            return ['status' => 'failed'];
        }

        $statusMap = [
            ScreenshotRequestRepository::STATUS_PENDING => 'pending',
            ScreenshotRequestRepository::STATUS_DONE    => 'done',
            ScreenshotRequestRepository::STATUS_FAILED  => 'failed',
        ];
        $status = $statusMap[(int) $request['status_div']] ?? 'failed';

        $result = ['status' => $status];
        if ($status === 'done') {
            $result['image_url'] = 'screenshot_image.php?user_uuid=' . rawurlencode($userUuid)
                . '&request_uuid=' . rawurlencode($requestUuid);
        }
        return $result;
    }

    /**
     * 管理画面の履歴表示用。ユーザーごとの過去の要求一覧を返す。
     */
    public function getHistory(string $userUuid): array
    {
        $statusMap = [
            ScreenshotRequestRepository::STATUS_PENDING => 'pending',
            ScreenshotRequestRepository::STATUS_DONE    => 'done',
            ScreenshotRequestRepository::STATUS_FAILED  => 'failed',
        ];

        $history = [];
        foreach ($this->screenshotRepository->findHistoryForUser($userUuid) as $row) {
            $status = $statusMap[(int) $row['status_div']] ?? 'failed';
            if ($status === 'pending' && $this->isStale($row)) {
                $status = 'failed';
            }
            $history[] = [
                'request_uuid'   => (string) $row['request_uuid'],
                'status'         => $status,
                'requested_date' => (string) $row['requested_date'],
                'admin_name'     => (string) ($row['admin_name'] ?? ''),
                'image_url'      => $status === 'done'
                    ? 'screenshot_image.php?user_uuid=' . rawurlencode($userUuid) . '&request_uuid=' . rawurlencode((string) $row['request_uuid'])
                    : null,
            ];
        }
        return $history;
    }

    /**
     * 認証済み配信コントローラーから呼ばれる。完了済みなら実ファイルの絶対パスを返す。
     */
    public function resolveImagePath(string $requestUuid, string $userUuid): ?string
    {
        $request = $this->screenshotRepository->find($requestUuid);
        if ($request === null
            || (string) $request['user_uuid'] !== $userUuid
            || (int) $request['status_div'] !== ScreenshotRequestRepository::STATUS_DONE
            || (string) $request['image_path'] === ''
        ) {
            return null;
        }

        $path = $this->storage->absolutePath((string) $request['image_path']);
        return is_file($path) ? $path : null;
    }

    private function isStale(array $request): bool
    {
        $requestedAt = strtotime((string) $request['requested_date']);
        if ($requestedAt === false) {
            return true;
        }
        return (time() - $requestedAt) > self::TIMEOUT_SECONDS;
    }
}
