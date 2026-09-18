<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ScreenshotService;
use App\Support\JsonResponder;
use App\Support\RequestContext;

final class ScreenshotStatusController
{
    private $auth;
    private $userRepository;
    private $screenshotService;
    private $request;

    public function __construct(
        UserAdminAuth $auth,
        UserRepositoryInterface $userRepository,
        ScreenshotService $screenshotService,
        RequestContext $request
    ) {
        $this->auth = $auth;
        $this->userRepository = $userRepository;
        $this->screenshotService = $screenshotService;
        $this->request = $request;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();

        $userUuid = trim((string) $this->request->query('user_uuid', ''));
        $requestUuid = trim((string) $this->request->query('request_uuid', ''));
        if ($userUuid === '' || $requestUuid === ''
            || !$this->userRepository->userAssignedToAdmin($userUuid, $this->auth->getLoginAdminUuid())
        ) {
            JsonResponder::error('unauthorized', 403);
            return;
        }

        JsonResponder::send($this->screenshotService->getStatus($requestUuid, $userUuid));
    }
}
