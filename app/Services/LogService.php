<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LogRepository;
use App\Support\AppConfig;

final class LogService
{
	private $logRepository;
	private $config;

	public function __construct(LogRepository $logRepository, AppConfig $config)
	{
		$this->logRepository = $logRepository;
		$this->config = $config;
	}

	public function buildPageData(string $userUuid, ?string $dateSt, ?string $dateEd): array
	{
		$normalizedSt = $this->normalizeDate($dateSt);
		$normalizedEd = $this->normalizeDate($dateEd);

		return array(
			'user_name' => $this->logRepository->findUserName($userUuid),
			'date_st' => $normalizedSt,
			'date_ed' => $normalizedEd,
			'contact' => $this->logRepository->findContacts($userUuid, $normalizedSt, $normalizedEd),
			'send' => $this->logRepository->findSends($userUuid, $normalizedSt, $normalizedEd),
			'divMap' => $this->logRepository->findDivMap(),
		);
	}

	public function buildContactCsv(array $contacts): string
	{
		$rows = array();
		$rows[] = $this->toCsvRow(array('日時', '種別', '状態', 'コメント'));
		foreach ($contacts as $contact) {
			$rows[] = $this->toCsvRow(array(
				(string) ($contact['contact_date'] ?? ''),
				(string) ($contact['contact_div'] ?? ''),
				(string) ($contact['confirm_div'] ?? ''),
				(string) ($contact['comment'] ?? ''),
			));
		}
		return implode("\r\n", $rows);
	}

	public function buildSendCsv(array $sends): string
	{
		$rows = array();
		$rows[] = $this->toCsvRow(array('日時', '種別', '状態'));
		foreach ($sends as $send) {
			$rows[] = $this->toCsvRow(array(
				(string) ($send['send_date'] ?? ''),
				(string) ($send['send_div'] ?? ''),
				(string) ($send['hook_div'] ?? ''),
			));
		}
		return implode("\r\n", $rows);
	}

	private function normalizeDate(?string $value): string
	{
		$value = trim((string) $value);
		if ($value === '') {
			return date('Y-m-d');
		}
		$ts = strtotime($value);
		return $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
	}

	private function toCsvRow(array $fields): string
	{
		$escaped = array();
		foreach ($fields as $field) {
			$value = str_replace('"', '""', (string) $field);
			$escaped[] = '"' . mb_convert_encoding($value, $this->config->csvCharset(), $this->config->charset()) . '"';
		}
		return implode(',', $escaped);
	}
}
