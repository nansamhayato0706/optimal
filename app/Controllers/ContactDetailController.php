<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ContactService;
use App\Support\JsonResponder;
use App\Support\RequestContext;
use App\Auth\UserAdminAuth;

final class ContactDetailController
{
	private $auth;
	private $contactService;
	private $request;

	public function __construct(UserAdminAuth $auth, ContactService $contactService, RequestContext $request)
	{
		$this->auth = $auth;
		$this->contactService = $contactService;
		$this->request = $request;
	}

	public function handle(): void
	{
		$this->auth->requireUserAdminRoute();

		$contactUuid = trim((string) $this->request->query('i', ''));
		$payload = $this->contactService->buildDetailPayload($contactUuid, $this->auth->getLoginAdminUuid());
		if ($payload === null) {
			JsonResponder::send(array('error' => 'not_found'), 404);
		}

		JsonResponder::send($payload);
	}
}
