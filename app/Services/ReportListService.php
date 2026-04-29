<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReportRepository;
use App\Support\AppConfig;

final class ReportListService
{
	private $reportRepository;
	private $config;

	public function __construct(ReportRepository $reportRepository, AppConfig $config)
	{
		$this->reportRepository = $reportRepository;
		$this->config = $config;
	}

	public function buildPageData(string $userUuid, string $dateStartInput, string $dateEndInput): array
	{
		$dateStart = $this->normalizeMonthDate($dateStartInput, false);
		$dateEnd = $this->normalizeMonthDate($dateEndInput !== '' ? $dateEndInput : $dateStartInput, true);

		return array(
			'user_uuid' => $userUuid,
			'user_name' => $this->reportRepository->findUserName($userUuid),
			'date_st' => $dateStartInput,
			'date_ed' => $dateEndInput,
			'report_date_new' => date('Y-m-d'),
			'report' => $this->reportRepository->findReports($userUuid, $dateStart, $dateEnd),
		);
	}

	public function buildCsv(string $userUuid, string $dateStartInput, string $dateEndInput): string
	{
		$dateStart = $this->normalizeMonthDate($dateStartInput, false);
		$dateEnd = $this->normalizeMonthDate($dateEndInput !== '' ? $dateEndInput : $dateStartInput, true);
		$reports = $this->reportRepository->findReportsForCsv($userUuid, $dateStart, $dateEnd);
		$reportUuids = array_column($reports, 'report_uuid');
		$objectiveMap = $this->reportRepository->findObjectiveMap($reportUuids);
		$outingMap = $this->reportRepository->findOutingMap($reportUuids);
		$divMap = $this->reportRepository->findDivMap();
		$objectiveHeader = $divMap['objective'] ?? array();
		$outingHeader = $divMap['outing'] ?? array();

		$rows = array();
		$header = array('日付', '就寝時間', '起床時間', '気分', '体調');
		foreach ($objectiveHeader as $label) {
			$header[] = $label;
		}
		$header[] = '服薬';
		$header[] = '服薬理由';
		foreach ($outingHeader as $label) {
			$header[] = $label;
		}
		$header[] = '会話した人数';
		$header[] = '訓練内容午前1';
		$header[] = '訓練内容午前2';
		$header[] = '訓練内容午前3';
		$header[] = '訓練内容午後1';
		$header[] = '訓練内容午後2';
		$header[] = '訓練内容午後3';
		$header[] = '開始時間';
		$header[] = '終了時間';
		$header[] = '昼休憩時間';
		$header[] = '途中休憩時間';
		$header[] = '感想午前';
		$header[] = '達成度午前';
		$header[] = '疲労度午前';
		$header[] = '感想午後';
		$header[] = '達成度午後';
		$header[] = '疲労度午後';
		$header[] = '備考';
		$header[] = 'コメント';
		$rows[] = $header;

		foreach ($reports as $report) {
			$row = array(
				$report['report_date'],
				$report['retiring_time'],
				$report['rising_time'],
				$this->getDivName($divMap, 'mood', (int) $report['mood_div']),
				$this->getDivName($divMap, 'condition', (int) $report['condition_div']),
			);

			$selectedObjectives = $objectiveMap[$report['report_uuid']] ?? array();
			foreach (array_keys($objectiveHeader) as $objectiveId) {
				$row[] = in_array((int) $objectiveId, $selectedObjectives, true) ? '○' : '';
			}

			$row[] = $this->getDivName($divMap, 'medicine', (int) $report['medicine_div']);
			$row[] = $report['medicine_reason'];

			$selectedOutings = $outingMap[$report['report_uuid']] ?? array();
			foreach (array_keys($outingHeader) as $outingId) {
				$row[] = in_array((int) $outingId, $selectedOutings, true) ? '○' : '';
			}

			$row[] = $this->getDivName($divMap, 'talk', (int) $report['talk_div']);
			$row[] = $report['training_am_1'];
			$row[] = $report['training_am_2'];
			$row[] = $report['training_am_3'];
			$row[] = $report['training_pm_1'];
			$row[] = $report['training_pm_2'];
			$row[] = $report['training_pm_3'];
			$row[] = $report['training_start_time'];
			$row[] = $report['training_end_time'];
			$row[] = $report['lunch_time'];
			$row[] = $report['break_time'];
			$row[] = $report['rethink_am'];
			$row[] = $report['achieve_am'];
			$row[] = $report['fatigue_am'];
			$row[] = $report['rethink_pm'];
			$row[] = $report['achieve_pm'];
			$row[] = $report['fatigue_pm'];
			$row[] = $report['remark'];
			$row[] = $report['charge_comment'];
			$rows[] = $row;
		}

		$stream = fopen('php://temp', 'r+');
		$outputCharset = $this->config->csvCharset();
		$sourceCharset = $this->config->charset();
		foreach ($rows as $row) {
			// Convert row values once here so the CSV writer only sees the target encoding.
			$encodedRow = array_map(static function ($value) use ($outputCharset, $sourceCharset): string {
				return mb_convert_encoding((string) $value, $outputCharset, $sourceCharset);
			}, $row);
			fputcsv($stream, $encodedRow);
		}
		rewind($stream);
		$csv = stream_get_contents($stream);
		fclose($stream);

		return $csv === false ? '' : $csv;
	}

	private function normalizeMonthDate(string $value, bool $isEnd): ?string
	{
		$trimmed = trim($value);
		if ($trimmed === '') {
			return null;
		}

		if (preg_match('/^\d{4}-\d{2}$/', $trimmed) !== 1) {
			return $trimmed;
		}

		$timestamp = strtotime($trimmed . '-01');
		if ($timestamp === false) {
			return $trimmed;
		}

		return $isEnd ? date('Y-m-t', $timestamp) : date('Y-m-01', $timestamp);
	}

	private function getDivName(array $divMap, string $parent, int $id): string
	{
		return (string) ($divMap[$parent][$id] ?? '');
	}
}
