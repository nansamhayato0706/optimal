<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminAuth;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\AdminFormService;
use App\Views\View;

final class AdminConfirmController
{
    private $auth;
    private $adminRepository;
    private $adminFormService;
    private $view;

    public function __construct(
        AdminAuth $auth,
        AdminRepositoryInterface $adminRepository,
        AdminFormService $adminFormService,
        View $view
    ) {
        $this->auth = $auth;
        $this->adminRepository = $adminRepository;
        $this->adminFormService = $adminFormService;
        $this->view = $view;
    }

    public function handle(): void
    {
        $this->auth->requireAdminRoute();
        $form = $this->adminFormService->getDraft();
        if ($form === null) {
            header('Location: admin.php');
            exit;
        }

        $this->view->render('admin/confirm', [
            'title' => '管理者登録確認',
            'form' => $form,
            'divMap' => $this->adminRepository->findDivMap(),
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
