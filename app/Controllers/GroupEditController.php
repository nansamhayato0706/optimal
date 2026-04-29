<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\GroupAuth;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Services\GroupFormService;
use App\Support\AppConfig;
use App\Support\RequestContext;
use App\Views\View;

final class GroupEditController
{
    private $auth;
    private $config;
    private $groupRepository;
    private $groupFormService;
    private $request;
    private $view;

    public function __construct(
        GroupAuth $auth,
        AppConfig $config,
        GroupRepositoryInterface $groupRepository,
        GroupFormService $groupFormService,
        RequestContext $request,
        View $view
    ) {
        $this->auth             = $auth;
        $this->config           = $config;
        $this->groupRepository  = $groupRepository;
        $this->groupFormService = $groupFormService;
        $this->request          = $request;
        $this->view             = $view;
    }

    public function handle(): void
    {
        $this->auth->requireGroupRoute();
        $errors = [];
        if ($this->request->isPost()) {
            csrf_verify_or_abort($this->request);
            $form   = $this->groupFormService->normalize($this->request->allPost());
            $errors = $this->groupFormService->validate($form);
            if ($errors === []) {
                $this->groupFormService->store($form);
                header('Location: group_confirm.php');
                exit;
            }
        } else {
            $groupParam = trim((string) $this->request->query('i', ''));
            $form       = $this->groupFormService->findForEdit($groupParam !== '' ? $groupParam : null);
        }
        $this->view->render('group/edit', [
            'title'        => $this->config->groupLabel() . '登録',
            'form'         => $form,
            'errors'       => $errors,
            'divMap'       => $this->groupRepository->findDivMap(),
            'headerLinks'  => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
