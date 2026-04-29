<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Services\ChatService;
use App\Support\RequestContext;
use App\Views\View;

final class ChatSendController
{
    private $auth;
    private $chatService;
    private $request;
    private $view;

    public function __construct(UserAdminAuth $auth, ChatService $chatService, RequestContext $request, View $view)
    {
        $this->auth = $auth;
        $this->chatService = $chatService;
        $this->request = $request;
        $this->view = $view;
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

        $result = $this->chatService->sendMessage(
            $userUuid,
            (string) $this->request->post('chat_text', ''),
            $this->auth->getLoginAdminUuid()
        );
        if ($result['success']) {
            header('Location: chat.php?i=' . rawurlencode($userUuid));
            exit;
        }

        $page = $this->chatService->buildPageData(
            $userUuid,
            trim((string) $this->request->post('insert_date', '')) !== '' ? trim((string) $this->request->post('insert_date', '')) : null,
            false
        );

        $this->view->render('chat/index', [
            'title' => 'チャット',
            'chatData' => $page,
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
            'errorMessage' => $result['error'],
        ]);
    }
}
