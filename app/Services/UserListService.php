<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\UserStatusSummaryRepository;

final class UserListService
{
	private $userRepository;
	private $userStatusSummaryRepository;

	public function __construct(UserRepository $userRepository, UserStatusSummaryRepository $userStatusSummaryRepository)
	{
		$this->userRepository = $userRepository;
		$this->userStatusSummaryRepository = $userStatusSummaryRepository;
	}

	public function buildPageData(string $groupUuid, string $adminUuid, string $deleteFlag, bool $allowRefresh, bool $doRefresh): array
	{
		$refreshCount = '';
		if ($allowRefresh && $doRefresh) {
			$refreshCount = (string) $this->userStatusSummaryRepository->refreshActiveByGroup($groupUuid);
		}

		$users = $this->userRepository->findUsers($groupUuid, $adminUuid, $deleteFlag);
		$summary = $this->buildSummary($users);

		return array(
			'users' => $users,
			'refresh_count' => $refreshCount,
			'training_start_count' => $summary['training_start_count'],
			'training_end_today_count' => $summary['training_end_today_count'],
			'report_notice_count' => $summary['report_notice_count'],
			'report_confirmed_count' => $summary['report_confirmed_count'],
			'chat_notice_count' => $summary['chat_notice_count'],
		);
	}

	public function buildStatusPayload(string $groupUuid, string $adminUuid, string $deleteFlag, array $divMap): array
	{
		$data = $this->userRepository->findUserStatuses($groupUuid, $adminUuid, $deleteFlag);
		$users = array();
		foreach ($data as $user) {
			$users[] = array(
				'user_uuid' => $user['user_uuid'],
				'contact_class' => $this->contactClass($user),
				'contact_html' => $this->contactHtml($user, $divMap),
				'report_class' => $this->reportClass($user),
				'report_html' => $this->reportHtml($user),
				'chat_class' => empty($user['chat_user_uuid']) ? '' : 'user_chat',
				'chat_html' => '<a href="chat.php?i=' . htmlspecialchars((string) $user['user_uuid'], ENT_QUOTES, 'UTF-8') . '">確認</a>',
			);
		}
		return array('users' => $users);
	}

	private function buildSummary(array $users): array
	{
		$result = array(
			'training_start_count' => 0,
			'training_end_today_count' => 0,
			'report_notice_count' => 0,
			'report_confirmed_count' => 0,
			'chat_notice_count' => 0,
		);
		$today = date('Y-m-d');
		foreach ($users as $user) {
			$contactDate = (string) ($user['contact_date'] ?? '');
			$contactDay = $contactDate !== '' ? date('Y-m-d', strtotime($contactDate)) : '';
			if ((int) ($user['contact_div'] ?? 0) === 1 && $contactDay === $today) {
				$result['training_start_count']++;
			}
			if ((int) ($user['contact_div'] ?? 0) === 2 && $contactDay === $today) {
				$result['training_end_today_count']++;
			}
			if (!empty($user['report_uuid']) && !$this->hasRegisteredReport($user)) {
				$result['report_notice_count']++;
			}
			if (!empty($user['report_uuid']) && $this->hasRegisteredReport($user)) {
				$result['report_confirmed_count']++;
			}
			if (!empty($user['chat_user_uuid'])) {
				$result['chat_notice_count']++;
			}
		}
		return $result;
	}

	private function contactClass(array $user): string
	{
		$confirmDiv = (int) ($user['confirm_div'] ?? 0);
		$contactDiv = (int) ($user['contact_div'] ?? 0);
		if ($confirmDiv === 1 || $confirmDiv === 2) {
			return 'user_contact_' . $contactDiv;
		}
		return 'user_contact_f_' . $contactDiv;
	}

	private function contactHtml(array $user, array $divMap): string
	{
		$confirmDiv = (int) ($user['confirm_div'] ?? 0);
		$contactDiv = (int) ($user['contact_div'] ?? 0);
		$dateStr = '';
		if (!empty($user['contact_date'])) {
			$ts = strtotime((string) $user['contact_date']);
			$dateStr = $ts === false ? '' : date('y/m/d H:i', $ts);
		}
		$contactLabel = htmlspecialchars((string) ($divMap['contact'][$contactDiv] ?? ''), ENT_QUOTES, 'UTF-8');
		$html = htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') . ' ' . $contactLabel;
		if ($confirmDiv === 1 || $confirmDiv === 2) {
			$contactUuid = htmlspecialchars((string) ($user['contact_uuid'] ?? ''), ENT_QUOTES, 'UTF-8');
			$html .= ' <a class="h_link user-status-link" href="contact.php?i=' . $contactUuid . '">確認</a>';
			return $html;
		}
		$confirmLabel = htmlspecialchars((string) ($divMap['confirm'][$confirmDiv] ?? ''), ENT_QUOTES, 'UTF-8');
		return $html . ' ' . $confirmLabel;
	}

	private function reportClass(array $user): string
	{
		if (empty($user['report_uuid'])) {
			return '';
		}
		if (!$this->hasRegisteredReport($user)) {
			return 'user_report';
		}
		return '';
	}

	private function reportHtml(array $user): string
	{
		$userUuid = htmlspecialchars((string) $user['user_uuid'], ENT_QUOTES, 'UTF-8');
		$reportUuid = htmlspecialchars((string) ($user['report_uuid'] ?? ''), ENT_QUOTES, 'UTF-8');
		if (empty($user['report_uuid'])) {
			return '<a href="report.php?i=' . $userUuid . '">一覧</a>';
		}
		if (!$this->hasRegisteredReport($user)) {
			return '<a href="report_edit.php?i=' . $reportUuid . '">編集</a>';
		}
		return '<a href="report_detail.php?i=' . $reportUuid . '">確認</a>';
	}

	private function hasRegisteredReport(array $user): bool
	{
		return trim((string) ($user['report_admin_uuid'] ?? '')) !== '';
	}
}
