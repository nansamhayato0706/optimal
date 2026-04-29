<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminAuth;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\AdminFormService;
use App\Support\RequestContext;
use App\Views\View;

final class AdminIndexController
{
    private $auth;
    private $adminRepository;
    private $adminFormService;
    private $request;
    private $view;

    public function __construct(
        AdminAuth $auth,
        AdminRepositoryInterface $adminRepository,
        AdminFormService $adminFormService,
        RequestContext $request,
        View $view
    ) {
        $this->auth = $auth;
        $this->adminRepository = $adminRepository;
        $this->adminFormService = $adminFormService;
        $this->request = $request;
        $this->view = $view;
    }

    public function handle(): void
    {
        $this->auth->requireAdminRoute();
        $this->adminFormService->clear();

        $groupParam = trim((string) $this->request->query('i', ''));
        $groupUuid = $this->auth->resolveCurrentGroup($groupParam !== '' ? $groupParam : null);

        $this->view->render('admin/index', [
            'title' => '管理者一覧',
            'admins' => $this->adminRepository->findAdmins($groupUuid),
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
