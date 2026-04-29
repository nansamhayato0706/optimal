<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReportFormService;
use App\Support\RequestContext;
use App\Support\ReportAuth;
use App\Support\ViewRenderer;

final class ReportCompleteController
{
	private $auth;
	private $formService;
	private $viewRenderer;
	private $request;

	public function __construct(ReportAuth $auth, ReportFormService $formService, ViewRenderer $viewRenderer, RequestContext $request)
	{
		$this->auth = $auth;
		$this->formService = $formService;
		$this->viewRenderer = $viewRenderer;
		$this->request = $request;
	}

	public function handle(): void
	{
		$this->auth->requireUserRoute();

		$draft = $this->formService->getStoredDraft();
		if ($draft === null) {
			header('Location: report.php');
			exit;
		}

		if ($this->request->isPost()) {
			\csrf_verify_or_abort($this->request);
			$draft['charge_comment'] = trim((string) $this->request->post('charge_comment', $draft['charge_comment'] ?? ''));
			$this->formService->replaceStoredDraft($draft);
		}

		$result = $this->formService->completeDraft(
			$this->auth->getLoginAuth(),
			$this->auth->getLoginId(),
			$this->auth->getLoginAdminUuid()
		);

		$this->viewRenderer->render('report/complete', array(
			'title' => '日報登録完了',
			'result' => $result,
			'headerLinks' => $this->auth->buildHeaderLinks(),
			'loginAdminId' => $this->auth->getLoginAdminId(),
		));
	}
}
