<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ScreenshotService;
use App\Support\JsonResponder;
use App\Support\RequestContext;

final class TrainingScreenshotController
{
    private $screenshotService;
    private $request;

    public function __construct(ScreenshotService $screenshotService, RequestContext $request)
    {
        $this->screenshotService = $screenshotService;
        $this->request = $request;
    }

    public function checkCommand(): void
    {
        if (!$this->request->isPost()) {
            header('Location: error.php');
            exit;
        }
        $token = trim((string) $this->request->post('value', ''));
        $requestUuid = $this->screenshotService->checkPendingForToken($token);
        JsonResponder::send(['screenshot_request_uuid' => $requestUuid]);
    }

    public function upload(): void
    {
        if (!$this->request->isPost()) {
            header('Location: error.php');
            exit;
        }
        $token = trim((string) $this->request->post('value', ''));
        $requestUuid = trim((string) $this->request->post('value2', ''));
        if ($requestUuid === '') {
            JsonResponder::error('invalid request');
            return;
        }

        JsonResponder::send(
            $this->screenshotService->receiveCapture($requestUuid, $token, $this->request->allFiles())
        );
    }
}
