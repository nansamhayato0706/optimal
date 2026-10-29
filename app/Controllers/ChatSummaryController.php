<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Services\ChatMonthlySummaryService;
use App\Services\UserListService;
use App\Support\RequestContext;
use App\Views\View;

final class ChatSummaryController
{
    private $auth;
    private $service;
    private $view;
    private $request;

    public function __construct(UserAdminAuth $auth, ChatMonthlySummaryService $service, View $view, RequestContext $request)
    {
        $this->auth = $auth;
        $this->service = $service;
        $this->view = $view;
        $this->request = $request;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();

        $deleteFlag = $this->auth->getDeleteFlag();
        if ($deleteFlag === '') {
            $deleteFlag = '0';
        }

        $page = $this->service->buildPageData(
            $this->auth->getLoginAdminUuid(),
            $deleteFlag,
            trim((string) $this->request->query('month', ''))
        );

        $this->view->render('chat_summary/index', [
            'title'         => 'チャット月間集計',
            'month'         => $page['month'],
            'monthLabel'    => $page['month_label'],
            'days'          => $page['days'],
            'rows'          => $page['rows'],
            'minExchanges'  => UserListService::CHAT_MIN_EXCHANGES,
            'headerLinks'   => $this->auth->buildHeaderLinks(),
            'loginAdminId'  => $this->auth->getLoginAdminId(),
        ]);
    }
}
