<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminAuth;
use App\Services\LinkService;
use App\Support\RequestContext;
use App\Views\View;

final class LinkIndexController
{
    private $auth;
    private $linkService;
    private $request;
    private $view;

    public function __construct(AdminAuth $auth, LinkService $linkService, RequestContext $request, View $view)
    {
        $this->auth = $auth;
        $this->linkService = $linkService;
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
            $rows = $this->linkService->normalizeRows($this->request->allPost());
            $errors = $this->linkService->validateRows($rows);
            if ($errors === []) {
                $result = $this->linkService->save($groupUuid, $rows, $this->auth->getLoginAdminUuid());
                $message = $result ? '外部リンクを更新しました。' : '外部リンクの更新に失敗しました。';
            }
        }

        $links = $this->request->isPost()
            ? $this->linkService->normalizeRows($this->request->allPost())
            : $this->linkService->getPageData($groupUuid);
        $links[] = ['link_url' => '', 'link_name' => '', 'sort' => ''];

        $this->view->render('link/index', [
            'title' => '外部リンク一覧',
            'links' => $links,
            'res' => $message,
            'errors' => $errors,
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
