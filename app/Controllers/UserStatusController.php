<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\UserStatusSummaryRepository;
use App\Services\UserListService;
use App\Support\JsonResponder;
use App\Support\RequestContext;
use App\Auth\UserAdminAuth;

final class UserStatusController
{
	private $auth;
	private $userRepository;
	private $userListService;
	private $summaryRepository;
	private $request;

	public function __construct(
		UserAdminAuth $auth,
		UserRepository $userRepository,
		UserListService $userListService,
		UserStatusSummaryRepository $summaryRepository,
		RequestContext $request
	) {
		$this->auth              = $auth;
		$this->userRepository    = $userRepository;
		$this->userListService   = $userListService;
		$this->summaryRepository = $summaryRepository;
		$this->request           = $request;
	}

	public function handle(): void
	{
		if (!in_array($this->auth->getLoginAuth(), array(1, 2, 3), true)) {
			JsonResponder::error('unauthorized', 401);
			return;
		}

		$groupUuid  = $this->auth->getLoginGroupId();
		$adminUuid  = $this->auth->getLoginAdminUuid();
		$deleteFlag = $this->auth->getDeleteFlag() === '' ? '0' : $this->auth->getDeleteFlag();

		$statusSignature = $this->summaryRepository->fetchGroupStatusSignature($groupUuid, $adminUuid, $deleteFlag);
		$etag = '"' . sha1($groupUuid . '|' . $adminUuid . '|' . $deleteFlag . '|' . $statusSignature) . '"';

		header('ETag: ' . $etag);
		$clientEtag = $this->request->header('If-None-Match');
		if ($clientEtag === $etag) {
			http_response_code(304);
			return;
		}

		$payload = $this->userListService->buildStatusPayload(
			$groupUuid,
			$adminUuid,
			$deleteFlag,
			$this->userRepository->findDivMap()
		);

		header('Cache-Control: no-cache');
		JsonResponder::send($payload);
	}
}
