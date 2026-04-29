<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\GroupAuth;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Services\GroupFormService;
use App\Support\AppConfig;
use App\Views\View;

final class GroupCompleteController
{
    private $auth;
    private $config;
    private $groupRepository;
    private $groupFormService;
    private $view;

    public function __construct(
        GroupAuth $auth,
        AppConfig $config,
        GroupRepositoryInterface $groupRepository,
        GroupFormService $groupFormService,
        View $view
    ) {
        $this->auth             = $auth;
        $this->config           = $config;
        $this->groupRepository  = $groupRepository;
        $this->groupFormService = $groupFormService;
        $this->view             = $view;
    }

    public function handle(): void
    {
        $this->auth->requireGroupRoute();
        $form = $this->groupFormService->getDraft();
        if ($form === []) {
            header('Location: group.php');
            exit;
        }
        $result = $this->groupRepository->saveGroup($form, $this->auth->getLoginAdminUuid());
        $this->groupFormService->clear();
        $this->view->render('group/complete', [
            'title'        => $this->config->groupLabel() . '登録完了',
            'result'       => $result,
            'headerLinks'  => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
