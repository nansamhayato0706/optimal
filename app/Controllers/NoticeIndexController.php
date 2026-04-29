<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminAuth;
use App\Services\NoticeService;
use App\Support\RequestContext;
use App\Views\View;

final class NoticeIndexController
{
    private $auth;
    private $noticeService;
    private $request;
    private $view;

    public function __construct(AdminAuth $auth, NoticeService $noticeService, RequestContext $request, View $view)
    {
        $this->auth = $auth;
        $this->noticeService = $noticeService;
        $this->request = $request;
        $this->view = $view;
    }

    public function handle(): void
    {
        $this->auth->requireAdminRoute();
        $groupParam = trim((string) $this->request->query('i', ''));
        $this->auth->resolveCurrentGroup($groupParam !== '' ? $groupParam : null);

        $groupUuid = $this->auth->getLoginGroupId();
        if ($groupUuid === '') {
            header('Location: login.php');
            exit;
        }

        $message = '';
        $errors = [];
        if ($this->request->isPost()) {
            csrf_verify_or_abort($this->request);
            $notification = trim((string) $this->request->post('notification', ''));
            $errors = $this->noticeService->validate($notification);
            if ($errors === []) {
                $result = $this->noticeService->update($groupUuid, $notification, $this->auth->getLoginAdminUuid());
                $message = $result ? 'お知らせを更新しました。' : 'お知らせの更新に失敗しました。';
            }
        }

        $notice = $this->noticeService->getPageData($groupUuid);
        if ($notice === null) {
            header('Location: error.php');
            exit;
        }
        if ($this->request->isPost() && $this->request->post('notification', null) !== null) {
            $notice['notification'] = trim((string) $this->request->post('notification', ''));
        }

        $this->view->render('notice/index', [
            'title' => 'お知らせ',
            'notice' => $notice,
            'res' => $message,
            'errors' => $errors,
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
