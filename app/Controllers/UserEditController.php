<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserFormService;
use App\Support\RequestContext;
use App\Views\View;

final class UserEditController
{
    private $auth;
    private $userRepository;
    private $userFormService;
    private $request;
    private $view;

    public function __construct(
        UserAdminAuth $auth,
        UserRepositoryInterface $userRepository,
        UserFormService $userFormService,
        RequestContext $request,
        View $view
    ) {
        $this->auth = $auth;
        $this->userRepository = $userRepository;
        $this->userFormService = $userFormService;
        $this->request = $request;
        $this->view = $view;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();

        if ($this->request->isPost()) {
            csrf_verify_or_abort($this->request);
            $result = $this->userFormService->validateAndStore($this->request->allPost(), $this->auth->getLoginGroupId());
            if ($result['ok']) {
                header('Location: user_confirm.php');
                exit;
            }
            $this->render($result['form'], $result['errors']);
            return;
        }

        $userParam = trim((string) $this->request->query('i', ''));
        $state = $this->userFormService->buildInitialState(
            $this->auth->getLoginGroupId(),
            $this->auth->getLoginAdminUuid(),
            $userParam !== '' ? $userParam : null
        );
        $this->render($state['form'], $state['errors']);
    }

    private function render(array $form, array $errors): void
    {
        $this->view->render('user/edit', [
            'title' => 'ユーザー登録',
            'form' => $form,
            'errors' => $errors,
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
            'divMap' => $this->userRepository->findDivMap(),
            'assignableAdmins' => $this->userRepository->findAssignableAdmins($this->auth->getLoginGroupId(), $this->auth->getLoginAdminUuid()),
        ]);
    }
}
