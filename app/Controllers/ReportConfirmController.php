<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ReportRepository;
use App\Services\ReportFormService;
use App\Support\ReportAuth;
use App\Support\ReportFieldLabels;
use App\Support\ViewRenderer;

final class ReportConfirmController
{
	private $auth;
	private $formService;
	private $reportRepository;
	private $viewRenderer;

	public function __construct(
		ReportAuth $auth,
		ReportFormService $formService,
		ReportRepository $reportRepository,
		ViewRenderer $viewRenderer
	) {
		$this->auth = $auth;
		$this->formService = $formService;
		$this->reportRepository = $reportRepository;
		$this->viewRenderer = $viewRenderer;
	}

	public function handle(): void
	{
		$this->auth->requireUserRoute();

		$draft = $this->formService->getStoredDraftWithDerived();
		if ($draft === null) {
			header('Location: report.php');
			exit;
		}

		$view = ($this->auth->getLoginAuth() === 0 && (int) ($draft['visual_impairment_flg'] ?? 0) === 1)
			? 'report/confirm_accessible'
			: 'report/confirm';

		$this->viewRenderer->render($view, array(
			'title' => '日報入力確認',
			'form' => $draft,
			'errors' => $this->formService->pullErrors(),
			'errorLabels' => ReportFieldLabels::forEdit(),
			'divMap' => $this->reportRepository->findDivMap(),
			'headerLinks' => $this->auth->buildHeaderLinks(),
			'loginAuth' => $this->auth->getLoginAuth(),
			'loginAdminId' => $this->auth->getLoginAdminId(),
		));
	}
}
