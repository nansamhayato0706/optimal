<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ContactService;
use App\Support\JsonResponder;
use App\Support\RequestContext;
use App\Auth\UserAdminAuth;

final class ContactUpdateController
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
		if (!$this->request->isPost()) {
			JsonResponder::send(array('error' => 'invalid_method'), 405);
		}
		if (!\App\Support\Csrf::verifyRequest($this->request)) {
			JsonResponder::send(array('error' => 'invalid_token'), 419);
		}

		$contactUuid = trim((string) $this->request->post('contact_uuid', ''));
		$result = $this->contactService->updateContact(
			$contactUuid,
			$this->request->allPost(),
			$this->auth->getLoginAdminUuid()
		);

		if ($result['status'] === 'not_found') {
			JsonResponder::send(array('error' => 'not_found'), 404);
		}
		if ($result['status'] === 'validation_failed') {
			JsonResponder::send(array(
				'error' => 'validation_failed',
				'errors' => $result['errors'],
			), 422);
		}
		if ($result['status'] !== 'ok') {
			JsonResponder::send(array('error' => 'update_failed'), 500);
		}

		JsonResponder::send(array(
			'success' => true,
			'user_uuid' => (string) $result['contact']['insert_uuid'],
			'contact_class' => $result['cell']['contact_class'],
			'contact_html' => $result['cell']['contact_html'],
		));
	}
}
