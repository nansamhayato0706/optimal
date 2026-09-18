<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ScreenshotService;
use App\Support\RequestContext;

final class ScreenshotImageController
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
            http_response_code(403);
            return;
        }

        $path = $this->screenshotService->resolveImagePath($requestUuid, $userUuid);
        if ($path === null) {
            http_response_code(404);
            return;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';

        header('Content-Type: ' . $mime);
        header('Cache-Control: private, no-store');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
    }
}
