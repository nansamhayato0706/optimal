<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Services\ChatService;
use App\Support\RequestContext;
use App\Views\View;

final class ChatIndexController
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
        $userParam = trim((string) $this->request->query('i', ''));
        $userUuid = $this->auth->resolveCurrentUserUuid($userParam !== '' ? $userParam : null);
        if ($userUuid === '') {
            header('Location: error.php');
            exit;
        }

        $page = $this->chatService->buildPageData(
            $userUuid,
            trim((string) $this->request->post('insert_date', '')) !== '' ? trim((string) $this->request->post('insert_date', '')) : null,
            trim((string) $this->request->post('history', '')) !== ''
        );

        $this->view->render('chat/index', [
            'title' => 'チャット',
            'chatData' => $page,
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
            'errorMessage' => '',
        ]);
    }
}
