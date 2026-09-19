<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Services\ChatService;
use App\Support\JsonResponder;
use App\Support\RequestContext;

final class ChatPollController
{
    private $auth;
    private $chatService;
    private $request;

    public function __construct(UserAdminAuth $auth, ChatService $chatService, RequestContext $request)
    {
        $this->auth = $auth;
        $this->chatService = $chatService;
        $this->request = $request;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();

        $userParam = trim((string) $this->request->query('i', ''));
        $userUuid = $this->auth->resolveCurrentUserUuid($userParam !== '' ? $userParam : null);
        if ($userUuid === '') {
            JsonResponder::error('not_found', 404);
            return;
        }

        $since = trim((string) $this->request->query('since', ''));
        if (!$this->isDateTime($since)) {
            JsonResponder::error('invalid_since');
            return;
        }

        JsonResponder::send($this->chatService->pollMessages($userUuid, $since));
    }

    private function isDateTime(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        return $date !== false && $date->format('Y-m-d H:i:s') === $value;
    }
}
