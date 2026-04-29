<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminAuth;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\AdminFormService;
use App\Views\View;

final class AdminCompleteController
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

        $result = $this->adminRepository->saveAdmin($form, $this->auth->getLoginId());
        if ($result) {
            $this->adminFormService->clear();
        }

        $this->view->render('admin/complete', [
            'title' => '管理者登録完了',
            'result' => $result,
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
