<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TrainingService;
use App\Support\JsonResponder;
use App\Support\RequestContext;
use App\Support\TrainingLoginPayload;

final class TrainingController
{
    private $trainingService;
    private $request;

    public function __construct(TrainingService $trainingService, RequestContext $request)
    {
        $this->trainingService = $trainingService;
        $this->request         = $request;
    }

    public function login(): void
    {
        if (!$this->request->isPost()) {
            header('Location: error.php');
            exit;
        }
        JsonResponder::send(
            $this->trainingService->login(
                TrainingLoginPayload::fromPost($this->request->allPost())
            )
        );
    }

    public function handleEvent(int $eventType): void
    {
        if (!$this->request->isPost()) {
            header('Location: error.php');
            exit;
        }
        JsonResponder::send(
            $this->trainingService->handleEvent(
                $eventType,
                $this->request->allPost(),
                $this->request->allFiles()
            )
        );
    }
}
