<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FirstService;
use App\Support\JsonResponder;
use App\Support\RequestContext;

final class FirstIndexController
{
    private $firstService;
    private $request;

    public function __construct(FirstService $firstService, RequestContext $request)
    {
        $this->firstService = $firstService;
        $this->request      = $request;
    }

    public function handle(): void
    {
        if (!$this->request->isPost()) {
            header('Location: error.php');
            exit;
        }
        JsonResponder::send(
            $this->firstService->bootstrap(trim((string) $this->request->post('value', '')))
        );
    }
}
