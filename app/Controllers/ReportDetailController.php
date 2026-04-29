<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReportDetailService;
use App\Repositories\ReportRepository;
use App\Support\RequestContext;
use App\Support\ReportAuth;
use App\Support\ViewRenderer;

final class ReportDetailController
{
	private $auth;
	private $reportDetailService;
	private $reportRepository;
	private $viewRenderer;
	private $request;

	public function __construct(
		ReportAuth $auth,
		ReportDetailService $reportDetailService,
		ReportRepository $reportRepository,
		ViewRenderer $viewRenderer,
		RequestContext $request
	) {
		$this->auth = $auth;
		$this->reportDetailService = $reportDetailService;
		$this->reportRepository = $reportRepository;
		$this->viewRenderer = $viewRenderer;
		$this->request = $request;
	}

	public function handle(): void
	{
		$this->auth->requireUserRoute();

		$reportUuid = trim((string) $this->request->query('i', ''));
		$detail = $this->reportDetailService->getByReportId($reportUuid);
		if ($detail === null) {
			header('Location: error.php');
			exit;
		}

		if ($this->auth->getLoginAuth() > 0) {
			$this->auth->resolveReportUserUuid((string) $detail['user_uuid']);
		} elseif ((string) $detail['user_uuid'] !== $this->auth->getLoginId()) {
			header('Location: error.php');
			exit;
		}

		$view = ($this->auth->getLoginAuth() === 0 && (int) ($detail['visual_impairment_flg'] ?? 0) === 1)
			? 'report/detail_accessible'
			: 'report/detail';

		$this->viewRenderer->render($view, array(
			'title' => '日報詳細',
			'detail' => $detail,
			'divMap' => $this->reportRepository->findDivMap(),
			'headerLinks' => $this->auth->buildHeaderLinks(),
			'loginAuth' => $this->auth->getLoginAuth(),
			'loginAdminId' => $this->auth->getLoginAdminId(),
			'currentUserUuid' => (string) $detail['user_uuid'],
		));
	}
}
