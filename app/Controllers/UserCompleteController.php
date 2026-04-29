<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserFormService;
use App\Views\View;

final class UserCompleteController
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

        $result = $this->userRepository->saveUser($form, $form['admin_uuid'] ?? [], $this->auth->getLoginAdminUuid());
        if ($result) {
            $this->userFormService->clear();
        }

        $this->view->render('user/complete', [
            'title' => 'ユーザー登録完了',
            'result' => $result,
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
