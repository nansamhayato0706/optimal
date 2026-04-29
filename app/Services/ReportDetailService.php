<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReportRepository;

final class ReportDetailService
{
	private $reportRepository;

	public function __construct(ReportRepository $reportRepository)
	{
		$this->reportRepository = $reportRepository;
	}

	public function getByReportId(string $reportUuid): ?array
	{
		$report = $this->reportRepository->findReportById($reportUuid);
		if ($report === null) {
			return null;
		}

		return $this->decorateReport($report);
	}

	public function getByUserAndDate(string $userUuid, string $reportDate): ?array
	{
		$report = $this->reportRepository->findLatestReportByUserAndDate($userUuid, $reportDate);
		if ($report === null) {
			return null;
		}

		return $this->decorateReport($report);
	}

	public function getByRange(string $userUuid, string $dateStart, string $dateEnd): array
	{
		$reports = $this->reportRepository->findReports(
			$userUuid,
			$this->normalizeDate($dateStart, false),
			$this->normalizeDate($dateEnd, true)
		);

		$result = array();
		foreach ($reports as $report) {
			$fullReport = $this->reportRepository->findReportById((string) $report['report_uuid']);
			if ($fullReport !== null) {
				$result[] = $this->decorateReport($fullReport);
			}
		}

		return $result;
	}

	private function decorateReport(array $report): array
	{
		$report['objective_div'] = $this->reportRepository->findObjectiveValues((string) $report['report_uuid']);
		$report['outing_div'] = $this->reportRepository->findOutingValues((string) $report['report_uuid']);
		$report['user_name'] = $this->reportRepository->findUserName((string) $report['user_uuid']);
		$report['visual_impairment_flg'] = $this->reportRepository->findUserVisualImpairmentFlag((string) $report['user_uuid']);

		if (($report['report_date'] ?? '') === '') {
			$report['report_date'] = date('Y-m-d');
		}

		$yesterday = date('Y-m-d', strtotime($report['report_date'] . ' -1 day'));
		$report['sleep_time'] = $this->diffTime(
			$yesterday . ' ' . (string) $report['retiring_time'],
			(string) $report['report_date'] . ' ' . (string) $report['rising_time'],
			'H時間i分'
		);
		$report['training_time'] = $this->diffTime(
			(string) $report['report_date'] . ' ' . (string) $report['training_start_time'],
			(string) $report['report_date'] . ' ' . (string) $report['training_end_time'],
			'H時間i分'
		);
		$report['lunch_break_time'] = (int) ($report['lunch_time'] ?? 0) + (int) ($report['break_time'] ?? 0);
		$report['work_time'] = $this->diffTime(
			(string) $report['report_date'] . ' ' . (string) $report['training_start_time'],
			date(
				'Y-m-d H:i:s',
				strtotime((string) $report['report_date'] . ' ' . (string) $report['training_end_time'] . ' -' . $report['lunch_break_time'] . ' minutes')
			),
			'H時間i分'
		);

		return $report;
	}

	private function diffTime(string $from, string $to, string $format): string
	{
		$fromSec = strtotime($from);
		$toSec = strtotime($to);
		if ($fromSec === false || $toSec === false) {
			return '';
		}

		return gmdate($format, $toSec - $fromSec);
	}

	private function normalizeDate(string $value, bool $isEnd): string
	{
		$trimmed = trim($value);
		if ($trimmed === '') {
			return '';
		}
		if (preg_match('/^\d{4}-\d{2}$/', $trimmed) === 1) {
			$timestamp = strtotime($trimmed . '-01');
			if ($timestamp !== false) {
				return $isEnd ? date('Y-m-t', $timestamp) : date('Y-m-01', $timestamp);
			}
		}

		return $trimmed;
	}
}
