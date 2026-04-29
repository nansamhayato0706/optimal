<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\GroupAuth;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Services\GroupFormService;
use App\Support\AppConfig;
use App\Views\View;

final class GroupIndexController
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
        $this->groupFormService->clear();
        $this->view->render('group/index', [
            'title'        => $this->config->groupLabel() . '一覧',
            'groups'       => $this->groupRepository->findGroups(),
            'headerLinks'  => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
        ]);
    }
}
