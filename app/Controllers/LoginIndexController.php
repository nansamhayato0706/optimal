<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LoginService;
use App\Support\AppConfig;
use App\Support\RequestContext;
use App\Support\SessionStore;
use App\Views\View;

final class LoginIndexController
{
    private $config;
    private $loginService;
    private $view;
    private $request;
    private $session;

    public function __construct(
        AppConfig $config,
        LoginService $loginService,
        View $view,
        RequestContext $request,
        SessionStore $session
    ) {
        $this->config       = $config;
        $this->loginService = $loginService;
        $this->view         = $view;
        $this->request      = $request;
        $this->session      = $session;
    }

    public function handle(): void
    {
        $params = ['admin_id' => '', 'admin_password' => '', 'error' => ''];

        if ($this->request->isPost()) {
            csrf_verify_or_abort($this->request);
            $params['admin_id']       = trim((string) $this->request->post('admin_id', ''));
            $params['admin_password'] = trim((string) $this->request->post('admin_password', ''));
            $result = $this->loginService->attempt($params['admin_id'], $params['admin_password']);
            if ($result['success']) {
                header('Location: ' . $result['redirect']);
                exit;
            }
            $params['error'] = $result['message'];
        } else {
            // GETアクセス = ログアウト（セッションクリア）
            $this->session->clear();
        }

        $this->view->render('login/index', [
            'title' => 'ログイン（' . $this->config->groupLabel() . '向け）',
            'form'  => $params,
        ]);
    }
}
