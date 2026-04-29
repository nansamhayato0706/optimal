<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ReportRepository;
use App\Services\ReportDetailService;
use App\Support\ReportAuth;
use App\Support\ReportPdfGenerator;
use App\Support\RequestContext;

final class ReportPdfController
{
	private $auth;
	private $reportDetailService;
	private $reportRepository;
	private $pdfGenerator;
	private $request;

	public function __construct(
		ReportAuth $auth,
		ReportDetailService $reportDetailService,
		ReportRepository $reportRepository,
		ReportPdfGenerator $pdfGenerator,
		RequestContext $request
	) {
		$this->auth = $auth;
		$this->reportDetailService = $reportDetailService;
		$this->reportRepository = $reportRepository;
		$this->pdfGenerator = $pdfGenerator;
		$this->request = $request;
	}

	public function handle(): void
	{
		$this->auth->requireUserRoute();
		if ($this->auth->getLoginAuth() < 1) {
			header('Location: error.php');
			exit;
		}

		$reportUuid = trim((string) $this->request->query('i', ''));
		$userUuid = trim((string) $this->request->query('user_uuid', ''));
		$dateStart = trim((string) $this->request->query('date_st', ''));
		$dateEnd = trim((string) $this->request->query('date_ed', ''));
		$defaultMonth = trim((string) $this->request->query('default_month', ''));

		if ($dateStart === '' && $dateEnd === '' && $defaultMonth !== '') {
			$dateStart = $defaultMonth;
			$dateEnd = $defaultMonth;
		}
		if ($dateStart !== '' && $dateEnd === '') {
			$dateEnd = $dateStart;
		}

		if ($userUuid !== '' && $dateStart !== '' && $dateEnd !== '') {
			$this->auth->resolveReportUserUuid($userUuid);
			$reports = $this->reportDetailService->getByRange($userUuid, $dateStart, $dateEnd);
			if ($reports === array()) {
				header('Location: error.php');
				exit;
			}

			$this->pdfGenerator->output(
				$reports,
				$this->reportRepository->findDivMap(),
				'report_' . str_replace('-', '', $dateStart) . '_' . str_replace('-', '', $dateEnd) . '_' . date('YmdHis')
			);
		}

		$detail = $this->reportDetailService->getByReportId($reportUuid);
		if ($detail === null) {
			header('Location: error.php');
			exit;
		}

		$this->auth->resolveReportUserUuid((string) $detail['user_uuid']);
		$this->pdfGenerator->output(
			array($detail),
			$this->reportRepository->findDivMap(),
			'report_' . date('YmdHis')
		);
	}
}
