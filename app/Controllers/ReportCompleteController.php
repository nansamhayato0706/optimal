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

		if (!$this->request->isPost()) {
			header('Location: report_confirm.php');
			exit;
		}

		\csrf_verify_or_abort($this->request);
		$draft['charge_comment'] = trim((string) $this->request->post('charge_comment', $draft['charge_comment'] ?? ''));
		$consented = trim((string) $this->request->post('consent_flg', '')) === '1';

		if ($this->auth->getLoginAuth() === 0 && !$consented) {
			$this->formService->storeErrors(
				array('consent_flg' => '本日の利用について確認し、チェックを入れてください。'),
				$draft
			);
			header('Location: report_confirm.php');
			exit;
		}

		$draft['consent_flg'] = $consented ? '1' : '0';
		$this->formService->replaceStoredDraft($draft);

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
