<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminAuth;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\AdminFormService;
use App\Support\RequestContext;
use App\Views\View;

final class AdminEditController
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

        if ($this->request->isPost()) {
            csrf_verify_or_abort($this->request);
            $form = $this->adminFormService->normalize($this->request->allPost(), $this->auth->getLoginGroupId());
            $errors = $this->adminFormService->validate($form);
            if ($errors === []) {
                $this->adminFormService->store($form);
                header('Location: admin_confirm.php');
                exit;
            }
        } else {
            $adminParam = trim((string) $this->request->query('i', ''));
            $form = $this->adminFormService->findForEdit(
                $this->auth->getLoginGroupId(),
                $adminParam !== '' ? $adminParam : null
            );
            $errors = [];
        }

        $this->view->render('admin/edit', [
            'title' => '管理者登録',
            'form' => $form,
            'errors' => $errors,
            'divMap' => $this->adminRepository->findDivMap(),
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
