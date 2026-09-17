<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Services\ChatService;
use App\Support\RequestContext;

final class ChatEditController
{
    private $auth;
    private $chatService;
    private $request;

    public function __construct(UserAdminAuth $auth, ChatService $chatService, RequestContext $request)
    {
        $this->auth = $auth;
        $this->chatService = $chatService;
        $this->request = $request;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();
        if (!$this->request->isPost()) {
            header('Location: error.php');
            exit;
        }
        csrf_verify_or_abort($this->request);

        $userUuid = $this->auth->resolveCurrentUserUuid(null);
        if ($userUuid === '') {
            header('Location: error.php');
            exit;
        }

        $chatUuid = trim((string) $this->request->post('chat_uuid', ''));
        $chatText = (string) $this->request->post('chat_text', '');
        if ($chatUuid !== '') {
            $this->chatService->editMessage($userUuid, $chatUuid, $chatText, $this->auth->getLoginAdminUuid());
        }

        header('Location: chat.php?i=' . rawurlencode($userUuid));
        exit;
    }
}
