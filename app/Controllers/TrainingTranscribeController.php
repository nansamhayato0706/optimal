<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\TrainingRepository;
use App\Services\OpenAiTranscriptionService;
use App\Services\TranscriptionRateLimiter;
use App\Support\JsonResponder;
use App\Support\RequestContext;
use App\Support\WavAudioInspector;

/**
 * WPF からの音声(WAV)を受け取り、OpenAI で文字起こしして返す。
 * 認証は WPF 連携と同じ login_uuid 方式（CSRF 対象外）。
 * 元は AzureSpeechTokenController と同じ位置づけのエンドポイント。
 */
final class TrainingTranscribeController
{
    const MAX_FILE_BYTES  = 4 * 1024 * 1024; // 16kHz/mono/16bit で概ね2分相当
    const MAX_AUDIO_SECONDS = 130.0;

    private $transcriptionService;
    private $rateLimiter;
    private $trainingRepository;
    private $request;

    public function __construct(
        OpenAiTranscriptionService $transcriptionService,
        TranscriptionRateLimiter $rateLimiter,
        TrainingRepository $trainingRepository,
        RequestContext $request
    ) {
        $this->transcriptionService = $transcriptionService;
        $this->rateLimiter          = $rateLimiter;
        $this->trainingRepository   = $trainingRepository;
        $this->request              = $request;
    }

    public function handle(): void
    {
        if (!$this->request->isPost()) {
            JsonResponder::send(['ok' => false, 'error' => 'method_not_allowed'], 405);
            return;
        }

        $token = trim((string) $this->request->post('value', ''));
        if ($token === '') {
            JsonResponder::send(['ok' => false, 'error' => 'token_required'], 400);
            return;
        }

        $user = $this->trainingRepository->findUserByLoginToken($token);
        if ($user === null) {
            JsonResponder::send(['ok' => false, 'error' => 'unauthorized'], 401);
            return;
        }

        if (!$this->transcriptionService->isEnabled()) {
            JsonResponder::send(['ok' => false, 'error' => 'openai_unavailable'], 503);
            return;
        }

        $file = $this->request->file('file');
        if (!is_array($file)
            || !isset($file['tmp_name'], $file['error'])
            || (int) $file['error'] !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) $file['tmp_name'])) {
            JsonResponder::send(['ok' => false, 'error' => 'file_required'], 400);
            return;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
            JsonResponder::send(['ok' => false, 'error' => 'file_too_large'], 400);
            return;
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'wav') {
            JsonResponder::send(['ok' => false, 'error' => 'unsupported_format'], 400);
            return;
        }

        $tmpPath = (string) $file['tmp_name'];
        $durationSeconds = WavAudioInspector::durationSeconds($tmpPath);
        if ($durationSeconds === null || $durationSeconds <= 0) {
            JsonResponder::send(['ok' => false, 'error' => 'invalid_wav'], 400);
            return;
        }
        if ($durationSeconds > self::MAX_AUDIO_SECONDS) {
            JsonResponder::send(['ok' => false, 'error' => 'audio_too_long'], 400);
            return;
        }

        $userUuid = (string) $user['user_uuid'];
        $limit = $this->rateLimiter->consume($userUuid, $durationSeconds);
        if (!$limit['allowed']) {
            $status = $limit['reason'] === 'storage_unavailable' ? 503 : 429;
            JsonResponder::send(['ok' => false, 'error' => $limit['reason']], $status);
            return;
        }

        $isCommandMode = $this->request->post('value2', '') === 'command';
        $text = $this->transcriptionService->transcribe($tmpPath, $originalName, $isCommandMode);
        if ($text === null) {
            JsonResponder::send(['ok' => false, 'error' => 'transcription_failed'], 503);
            return;
        }

        JsonResponder::send(['ok' => true, 'text' => $text]);
    }
}
