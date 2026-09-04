<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Services\ReportDailyListService;
use App\Support\RequestContext;
use App\Views\View;

final class ReportDailyIndexController
{
    private $auth;
    private $service;
    private $view;
    private $request;

    public function __construct(UserAdminAuth $auth, ReportDailyListService $service, View $view, RequestContext $request)
    {
        $this->auth = $auth;
        $this->service = $service;
        $this->view = $view;
        $this->request = $request;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();

        $date = trim((string) $this->request->post('date', date('Y-m-d')));
        if ($date === '') {
            $date = date('Y-m-d');
        }

        $page = $this->service->buildPageData(
            $this->auth->getLoginGroupUuid(),
            $this->auth->getLoginAdminUuid(),
            $date
        );

        $this->view->render('report/daily', [
            'title' => '全員日報一覧',
            'date' => $page['date'],
            'rows' => $page['rows'],
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
