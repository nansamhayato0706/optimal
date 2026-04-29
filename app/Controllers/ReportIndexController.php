<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReportListService;
use App\Support\RequestContext;
use App\Support\ReportAuth;
use App\Support\ViewRenderer;

final class ReportIndexController
{
	private $auth;
	private $reportListService;
	private $viewRenderer;
	private $request;

	public function __construct(
		ReportAuth $auth,
		ReportListService $reportListService,
		ViewRenderer $viewRenderer,
		RequestContext $request
	) {
		$this->auth = $auth;
		$this->reportListService = $reportListService;
		$this->viewRenderer = $viewRenderer;
		$this->request = $request;
	}

	public function handle(): void
	{
		$this->auth->requireUserRoute();

		$dateStart = trim((string) $this->request->post('date_st', date('Y-m')));
		$dateEnd = trim((string) $this->request->post('date_ed', ''));
		$requestedUserUuid = ($value = $this->request->query('i')) !== null ? trim((string) $value) : null;
		$userUuid = $this->auth->resolveReportUserUuid($requestedUserUuid);

		if ($this->isCsvRequest() && $this->auth->getLoginAuth() > 0) {
			$this->streamCsv($userUuid, $dateStart, $dateEnd);
		}

		$pageData = $this->reportListService->buildPageData($userUuid, $dateStart, $dateEnd);
		$this->viewRenderer->render('report/index', array(
			'title' => '日報一覧',
			'pageData' => $pageData,
			'headerLinks' => $this->auth->buildHeaderLinks(),
			'loginAuth' => $this->auth->getLoginAuth(),
			'loginAdminId' => $this->auth->getLoginAdminId(),
			'currentUserUuid' => $userUuid,
		));
	}

	private function isCsvRequest(): bool
	{
		return $this->request->isPost()
			&& trim((string) $this->request->post('report', '')) !== '';
	}

	private function streamCsv(string $userUuid, string $dateStart, string $dateEnd): void
	{
		$csv = $this->reportListService->buildCsv($userUuid, $dateStart, $dateEnd);
		$fileName = 'report_' . date('YmdHis') . '.csv';

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $fileName . ';');
		echo $csv;
		exit;
	}
}
