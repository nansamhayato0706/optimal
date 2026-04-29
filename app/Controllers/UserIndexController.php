<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserListService;
use App\Support\AppConfig;
use App\Support\RequestContext;
use App\Views\View;

final class UserIndexController
{
    private $auth;
    private $config;
    private $repository;
    private $userListService;
    private $view;
    private $request;

    public function __construct(
        UserAdminAuth $auth,
        AppConfig $config,
        UserRepositoryInterface $repository,
        UserListService $userListService,
        View $view,
        RequestContext $request
    ) {
        $this->auth            = $auth;
        $this->config          = $config;
        $this->repository      = $repository;
        $this->userListService = $userListService;
        $this->view            = $view;
        $this->request         = $request;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();
        $requestedAdminUuid = $this->request->query('i');
        $this->auth->resolveCurrentAdminUuid($requestedAdminUuid !== null ? trim((string) $requestedAdminUuid) : null);

        $deleteFlagInput = $this->request->post('delete_flg');
        $deleteFlag = $deleteFlagInput !== null
            ? trim((string) $deleteFlagInput)
            : $this->auth->getDeleteFlag();
        if ($deleteFlag === '') {
            $deleteFlag = '0';
        }
        $this->auth->setDeleteFlag($deleteFlag);

        $allowRefresh = $this->config->statusSummaryRebuildEnabled()
            && $this->auth->getLoginGroupUuid() !== '';
        $doRefresh = $this->request->isPost()
            && trim((string) $this->request->post('status_summary_refresh', '')) !== '';

        if ($doRefresh) {
            csrf_verify_or_abort($this->request);
        }

        $page = $this->userListService->buildPageData(
            $this->auth->getLoginGroupUuid(),
            $this->auth->getLoginAdminUuid(),
            $deleteFlag,
            $allowRefresh,
            $doRefresh
        );

        $this->view->render('user/index', [
            'title'                        => 'ユーザー一覧',
            'users'                        => $page['users'],
            'deleteFlag'                   => $deleteFlag,
            'headerLinks'                  => $this->auth->buildHeaderLinks(),
            'loginAdminId'                 => $this->auth->getLoginAdminId(),
            'statusSummaryRefreshEnabled'  => $allowRefresh,
            'statusSummaryRefreshCount'    => $page['refresh_count'],
            'trainingStartCount'           => $page['training_start_count'],
            'trainingEndTodayCount'        => $page['training_end_today_count'],
            'reportNoticeCount'            => $page['report_notice_count'],
            'reportConfirmedCount'         => $page['report_confirmed_count'],
            'chatNoticeCount'              => $page['chat_notice_count'],
            'divMap'                       => $this->repository->findDivMap(),
        ]);
    }
}
