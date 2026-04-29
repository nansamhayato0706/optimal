<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserFormService;
use App\Views\View;

final class UserConfirmController
{
    private $auth;
    private $userRepository;
    private $userFormService;
    private $view;

    public function __construct(
        UserAdminAuth $auth,
        UserRepositoryInterface $userRepository,
        UserFormService $userFormService,
        View $view
    ) {
        $this->auth = $auth;
        $this->userRepository = $userRepository;
        $this->userFormService = $userFormService;
        $this->view = $view;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();
        $form = $this->userFormService->getStored();
        if ($form === null) {
            header('Location: user.php');
            exit;
        }

        $this->view->render('user/confirm', [
            'title' => 'ユーザー登録確認',
            'form' => $form,
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
            'divMap' => $this->userRepository->findDivMap(),
            'assignableAdmins' => $this->userRepository->findAssignableAdmins($this->auth->getLoginGroupId(), $this->auth->getLoginAdminUuid()),
        ]);
    }
}
