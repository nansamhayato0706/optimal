<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ScreenshotService;
use App\Support\JsonResponder;
use App\Support\RequestContext;

final class ScreenshotDeleteController
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
        if (!$this->request->isPost()) {
            JsonResponder::error('method not allowed', 405);
            return;
        }
        csrf_verify_or_abort($this->request);

        $userUuid = trim((string) $this->request->post('user_uuid', ''));
        $requestUuid = trim((string) $this->request->post('request_uuid', ''));
        if ($userUuid === '' || $requestUuid === ''
            || !$this->userRepository->userAssignedToAdmin($userUuid, $this->auth->getLoginAdminUuid())
        ) {
            JsonResponder::error('unauthorized', 403);
            return;
        }

        JsonResponder::send($this->screenshotService->deleteCapture(
            $requestUuid,
            $userUuid,
            $this->auth->getLoginAdminUuid()
        ));
    }
}
