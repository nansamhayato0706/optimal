<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\UserListService;
use App\Support\JsonResponder;
use App\Support\RequestContext;
use App\Auth\UserAdminAuth;

final class UserStatusController
{
	private $auth;
	private $userRepository;
	private $userListService;
	private $request;

	public function __construct(UserAdminAuth $auth, UserRepository $userRepository, UserListService $userListService, RequestContext $request)
	{
		$this->auth = $auth;
		$this->userRepository = $userRepository;
		$this->userListService = $userListService;
		$this->request = $request;
	}

	public function handle(): void
	{
		if (!in_array($this->auth->getLoginAuth(), array(1, 2, 3), true)) {
			JsonResponder::error('unauthorized', 401);
			return;
		}

		$payload = $this->userListService->buildStatusPayload(
			$this->auth->getLoginGroupId(),
			$this->auth->getLoginAdminUuid(),
			$this->auth->getDeleteFlag() === '' ? '0' : $this->auth->getDeleteFlag(),
			$this->userRepository->findDivMap()
		);

		$etag = '"' . md5(json_encode($payload)) . '"';
		$clientEtag = $this->request->header('If-None-Match');
		if ($clientEtag === $etag) {
			http_response_code(304);
			return;
		}
		header('ETag: ' . $etag);
		header('Cache-Control: no-cache');
		JsonResponder::send($payload);
	}
}
