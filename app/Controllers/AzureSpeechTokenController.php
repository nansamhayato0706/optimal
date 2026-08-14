<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\TrainingRepository;
use App\Services\AzureSpeechTokenService;
use App\Support\JsonResponder;
use App\Support\RequestContext;

/**
 * WPF からのリクエストを受けて Azure Speech の短期認可トークンを返す。
 * 認証は WPF 連携と同じ login_uuid 方式（CSRF 対象外）。
 */
final class AzureSpeechTokenController
{
    private $tokenService;
    private $trainingRepository;
    private $request;

    public function __construct(
        AzureSpeechTokenService $tokenService,
        TrainingRepository $trainingRepository,
        RequestContext $request
    ) {
        $this->tokenService       = $tokenService;
        $this->trainingRepository = $trainingRepository;
        $this->request            = $request;
    }

    public function handle(): void
    {
        if (!$this->request->isPost()) {
            JsonResponder::send(['ok' => false, 'error' => 'method_not_allowed'], 405);
            return;
        }

        $token = trim((string) $this->request->post('token', ''));
        if ($token === '') {
            JsonResponder::send(['ok' => false, 'error' => 'token_required'], 400);
            return;
        }

        $user = $this->trainingRepository->findUserByLoginToken($token);
        if ($user === null) {
            JsonResponder::send(['ok' => false, 'error' => 'unauthorized'], 401);
            return;
        }

        $issued = $this->tokenService->issueToken();
        if ($issued === null) {
            JsonResponder::send(['ok' => false, 'error' => 'azure_speech_unavailable'], 503);
            return;
        }

        JsonResponder::send([
            'ok'         => true,
            'token'      => $issued['token'],
            'region'     => $issued['region'],
            'expires_in' => $issued['expires_in'],
        ]);
    }
}
