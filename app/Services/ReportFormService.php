<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReportRepository;
use App\Repositories\UserStatusSummaryRepository;
use App\Support\AppConfig;
use App\Support\SessionStore;

final class ReportFormService
{
	private const INPUT_SESSION_KEY = 'report_modern_input';
	private const ERROR_SESSION_KEY = 'report_modern_errors';

	private $reportRepository;
	private $userStatusSummaryRepository;
	private $sessionStore;
	private $config;

	public function __construct(ReportRepository $reportRepository, UserStatusSummaryRepository $userStatusSummaryRepository, SessionStore $sessionStore, AppConfig $config)
	{
		$this->reportRepository = $reportRepository;
		$this->userStatusSummaryRepository = $userStatusSummaryRepository;
		$this->sessionStore = $sessionStore;
		$this->config = $config;
	}

	public function getFormState(int $loginAuth, string $loginId, ?string $reportUuid, ?string $targetUserUuid, ?string $targetReportDate): array
	{
		$draft = $this->sessionStore->get(self::INPUT_SESSION_KEY);
		$errors = $this->sessionStore->get(self::ERROR_SESSION_KEY, array());
		$this->sessionStore->forget(self::INPUT_SESSION_KEY);
		$this->sessionStore->forget(self::ERROR_SESSION_KEY);

		if (is_array($draft)) {
			$draft += $this->defaultFormData();
			return array(
				'form' => $this->computeDerived($draft),
				'errors' => is_array($errors) ? $errors : array(),
				'mode' => $this->resolveMode($loginAuth, $draft),
			);
		}

		$form = $this->defaultFormData();
		$errors = array();

		if ($reportUuid !== null && $reportUuid !== '') {
			$report = $this->reportRepository->findReportById($reportUuid);
			if ($report !== null) {
				$form = $this->mapReportToForm($report);
			}
		} elseif ($loginAuth > 0 && $targetUserUuid !== null && $targetUserUuid !== '' && $targetReportDate !== null && $targetReportDate !== '') {
			$count = $this->reportRepository->countReportsByUserAndDate($targetUserUuid, $targetReportDate);
			if ($count > 1) {
				$form['user_uuid'] = $targetUserUuid;
				$form['report_date'] = $targetReportDate;
				$errors['report_date'] = '同一ユーザー・同一日付の日報が複数件あります。重複データを整理してから編集してください。';
			} else {
				$report = $this->reportRepository->findLatestReportByUserAndDate($targetUserUuid, $targetReportDate);
				if ($report !== null) {
					$form = $this->mapReportToForm($report);
				} else {
					$form['user_uuid'] = $targetUserUuid;
					$form['report_date'] = $targetReportDate;
				}
			}
		} elseif ($loginAuth === 0) {
			$report = $this->reportRepository->findLatestReportByUserAndDate($loginId, date('Y-m-d'));
			if ($report !== null) {
				$form = $this->mapReportToForm($report);
			} else {
				$form['user_uuid'] = $loginId;
			}
		}

		return array(
			'form' => $this->computeDerived($form),
			'errors' => $errors,
			'mode' => $this->resolveMode($loginAuth, $form),
		);
	}

	public function validateAndStoreDraft(array $input, int $loginAuth, string $loginId, string $loginAdminUuid): array
	{
		$form = $this->buildFormFromInput($input);
		if ($loginAuth === 0) {
			$form['user_uuid'] = $loginId;
		}

		if ($loginAuth > 0) {
			$accessError = $this->validateAdminAccess($form, $loginAdminUuid);
			if ($accessError !== null) {
				return array(
					'ok' => false,
					'form' => $this->computeDerived($form),
					'errors' => $accessError,
					'mode' => $this->resolveMode($loginAuth, $form),
				);
			}
		}

		$errors = $this->validate($form, $loginAuth);
		if ($errors !== array()) {
			return array(
				'ok' => false,
				'form' => $this->computeDerived($form),
				'errors' => $errors,
				'mode' => $this->resolveMode($loginAuth, $form),
			);
		}

		if ($loginAuth === 0) {
			$form['admin_uuid'] = '';
			$form['reply'] = '';
			$form['charge_comment'] = '';
		} elseif (($form['report_uuid'] ?? '') === '') {
			$form['admin_uuid'] = '';
		}

		$form['actor_uuid'] = $loginAuth === 0 ? $loginId : $loginAdminUuid;
		$this->sessionStore->put(self::INPUT_SESSION_KEY, $form);

		return array(
			'ok' => true,
			'form' => $this->computeDerived($form),
			'errors' => array(),
			'mode' => $this->resolveMode($loginAuth, $form),
		);
	}

	public function getStoredDraft(): ?array
	{
		$draft = $this->sessionStore->get(self::INPUT_SESSION_KEY);
		return is_array($draft) ? $draft : null;
	}

	public function pullErrors(): array
	{
		$errors = $this->sessionStore->get(self::ERROR_SESSION_KEY, array());
		$this->sessionStore->forget(self::ERROR_SESSION_KEY);
		return is_array($errors) ? $errors : array();
	}

	public function getStoredDraftWithDerived(): ?array
	{
		$draft = $this->getStoredDraft();
		return $draft !== null ? $this->computeDerived($draft) : null;
	}

	public function clearDraft(): void
	{
		$this->sessionStore->forget(self::INPUT_SESSION_KEY);
		$this->sessionStore->forget(self::ERROR_SESSION_KEY);
	}

	public function storeErrors(array $errors, array $form): void
	{
		$this->sessionStore->put(self::ERROR_SESSION_KEY, $errors);
		$this->sessionStore->put(self::INPUT_SESSION_KEY, $form);
	}

	public function replaceStoredDraft(array $form): void
	{
		$this->sessionStore->put(self::INPUT_SESSION_KEY, $form);
	}

	public function completeDraft(int $loginAuth, string $loginId, string $loginAdminUuid): bool
	{
		$draft = $this->getStoredDraft();
		if ($draft === null) {
			return false;
		}

		if ($loginAuth > 0) {
			$accessError = $this->validateAdminAccess($draft, $loginAdminUuid);
			if ($accessError !== null) {
				$this->storeErrors($accessError, $draft);
				return false;
			}
		}

		$ok = false;
		if ($loginAuth === 0 || ($loginAuth > 0 && ($draft['report_uuid'] ?? '') === '')) {
			if ($loginAuth > 0 && ($draft['report_uuid'] ?? '') === '') {
				$count = $this->reportRepository->countReportsByUserAndDate((string) $draft['user_uuid'], (string) $draft['report_date']);
				if ($count > 1) {
					$this->storeErrors(
						array('report_date' => '同一ユーザー・同一日付の日報が複数件あります。重複データを整理してから登録してください。'),
						$draft
					);
					return false;
				}
				if ($count === 1) {
					$existing = $this->reportRepository->findLatestReportByUserAndDate((string) $draft['user_uuid'], (string) $draft['report_date']);
					if ($existing !== null) {
						$draft['report_uuid'] = $existing['report_uuid'];
					}
				}
			}

			$draft['actor_uuid'] = $loginAuth === 0 ? $loginId : $loginAdminUuid;
			$ok = $this->reportRepository->saveReportDraft(
				$draft,
				$draft['objective_div'] ?? array(),
				$draft['outing_div'] ?? array()
			);
		} else {
			$ok = $this->reportRepository->saveAdminComment(
				(string) $draft['report_uuid'],
				$loginAdminUuid,
				(string) ($draft['reply'] ?? ''),
				(string) ($draft['charge_comment'] ?? ''),
				$loginAdminUuid
			);
		}

		if ($ok) {
			$userUuid = (string) ($draft['user_uuid'] ?? '');
			if ($userUuid !== '') {
				$this->userStatusSummaryRepository->refreshUserStatusSummary($userUuid);
			}
			if ($loginAuth === 0) {
				$this->reportRepository->insertSendLog($loginId);
			}
			$this->clearDraft();
		}

		return $ok;
	}

	public function defaultFormData(): array
	{
		return array(
			'report_uuid' => '',
			'user_uuid' => '',
			'user_name' => '',
			'visual_impairment_flg' => '0',
			'report_date' => date('Y-m-d'),
			'retiring_time' => '',
			'rising_time' => '',
			'mood_div' => '',
			'condition_div' => '',
			'objective_div' => array(),
			'medicine_div' => '',
			'medicine_reason' => '',
			'outing_div' => array(),
			'talk_div' => '',
			'training_am_1' => '',
			'training_am_2' => '',
			'training_am_3' => '',
			'training_pm_1' => '',
			'training_pm_2' => '',
			'training_pm_3' => '',
			'training_start_time' => '',
			'training_end_time' => '',
			'lunch_time' => '0',
			'break_time' => '0',
			'rethink_am' => '',
			'achieve_am' => '0',
			'fatigue_am' => '0',
			'rethink_pm' => '',
			'achieve_pm' => '0',
			'fatigue_pm' => '0',
			'remark' => '',
			'admin_uuid' => '',
			'reply' => '',
			'charge_comment' => '',
			'consent_flg' => '0',
		);
	}

	private function mapReportToForm(array $report): array
	{
		$form = $this->defaultFormData();
		foreach ($form as $key => $_) {
			if (array_key_exists($key, $report)) {
				$form[$key] = $report[$key];
			}
		}
		$form['report_date'] = $this->normalizeDateValue((string) ($form['report_date'] ?? ''));
		foreach (array('retiring_time', 'rising_time', 'training_start_time', 'training_end_time') as $field) {
			$form[$field] = $this->normalizeTimeValue((string) ($form[$field] ?? ''));
		}
		$form['objective_div'] = $this->reportRepository->findObjectiveValues((string) $report['report_uuid']);
		$form['outing_div'] = $this->reportRepository->findOutingValues((string) $report['report_uuid']);
		return $form;
	}

	private function buildFormFromInput(array $input): array
	{
		$reportUuid = trim((string) ($input['report_uuid'] ?? ''));
		if ($reportUuid === '') {
			return $this->normalizeInput($input);
		}

		$report = $this->reportRepository->findReportById($reportUuid);
		$form = $report !== null ? $this->mapReportToForm($report) : $this->defaultFormData();
		$normalized = $this->normalizeInput($input);

		foreach ($normalized as $key => $value) {
			if (!array_key_exists($key, $input)) {
				continue;
			}
			$form[$key] = $value;
		}

		return $form;
	}

	private function normalizeInput(array $input): array
	{
		$form = $this->defaultFormData();
		foreach ($form as $key => $default) {
			if (!array_key_exists($key, $input)) {
				continue;
			}
			if (is_array($default)) {
				$form[$key] = array_values(array_filter(array_map('intval', (array) $input[$key]), static function ($value): bool {
					return $value !== 0;
				}));
			} else {
				$form[$key] = trim((string) $input[$key]);
			}
		}
		return $form;
	}

	private function normalizeDateValue(string $value): string
	{
		if ($value === '') {
			return '';
		}

		$ts = strtotime($value);
		return $ts === false ? $value : date('Y-m-d', $ts);
	}

	private function normalizeTimeValue(string $value): string
	{
		if ($value === '') {
			return '';
		}

		if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
			return $value;
		}

		if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
			return substr($value, 0, 5);
		}

		$ts = strtotime($value);
		return $ts === false ? $value : date('H:i', $ts);
	}

	private function validate(array $form, int $loginAuth): array
	{
		$errors = array();

		$requiredDateFields = array('report_date');
		foreach ($requiredDateFields as $field) {
			if (!$this->isDate($form[$field] ?? '')) {
				$errors[$field] = '日付の形式が正しくありません。';
			}
		}

		foreach (array('retiring_time', 'rising_time', 'training_start_time', 'training_end_time') as $field) {
			if (!$this->isOptionalTime($form[$field] ?? '')) {
				$errors[$field] = '時間の形式が正しくありません。';
			}
		}

		foreach (array('mood_div', 'condition_div', 'medicine_div', 'talk_div', 'achieve_am', 'fatigue_am', 'achieve_pm', 'fatigue_pm', 'lunch_time', 'break_time') as $field) {
			if (!$this->isNumericRequired($form[$field] ?? '')) {
				$errors[$field] = '選択してください。';
			}
		}

		foreach (array(
			'training_am_1' => 255, 'training_am_2' => 255, 'training_am_3' => 255,
			'training_pm_1' => 255, 'training_pm_2' => 255, 'training_pm_3' => 255,
			'medicine_reason' => 255, 'rethink_am' => 255, 'rethink_pm' => 255,
			'remark' => 255, 'reply' => 255, 'charge_comment' => 255,
		) as $field => $length) {
			if (!$this->isMaxLength($form[$field] ?? '', $length)) {
				$errors[$field] = $length . '文字以内で入力してください。';
			}
		}

		if (!$this->isMaxLineCount($form['reply'] ?? '', 5)) {
			$errors['reply'] = '5行以内で入力してください。';
		}

		if (!$this->isMaxLineCount($form['charge_comment'] ?? '', 5)) {
			$errors['charge_comment'] = '5行以内で入力してください。';
		}

		$trainingFields = array('training_am_1', 'training_am_2', 'training_am_3', 'training_pm_1', 'training_pm_2', 'training_pm_3');
		$allTrainingEmpty = array_reduce($trainingFields, static function (bool $carry, string $field) use ($form): bool {
			return $carry && ($form[$field] ?? '') === '';
		}, true);
		if ($allTrainingEmpty) {
			$errors['training_content'] = '訓練内容を少なくとも1つ入力してください。';
		}

		if ($loginAuth > 0 && ($form['report_uuid'] ?? '') !== '') {
			$locked = ($form['admin_uuid'] ?? '') !== '';
			if ($locked) {
				$errors['report_uuid'] = 'ロック済みの日報です。';
			}
		}

		if (($form['user_uuid'] ?? '') === '') {
			$errors['user_uuid'] = '対象ユーザーが不正です。';
		}

		return $errors;
	}

	private function computeDerived(array $form): array
	{
		$form['user_name'] = $this->reportRepository->findUserName((string) ($form['user_uuid'] ?? ''));
		$form['visual_impairment_flg'] = (string) $this->reportRepository->findUserVisualImpairmentFlag((string) ($form['user_uuid'] ?? ''));

		$reportDate = (string) ($form['report_date'] ?? date('Y-m-d'));
		$yesterday = date('Y-m-d', strtotime($reportDate . ' -1 day'));
		$form['sleep_time'] = $this->diffTime($yesterday . ' ' . (string) $form['retiring_time'], $reportDate . ' ' . (string) $form['rising_time']);
		$form['training_time'] = $this->diffTime($reportDate . ' ' . (string) $form['training_start_time'], $reportDate . ' ' . (string) $form['training_end_time']);
		$form['lunch_break_time'] = (int) ($form['lunch_time'] ?? 0) + (int) ($form['break_time'] ?? 0);
		$form['work_time'] = '';
		$workEndSec = strtotime($reportDate . ' ' . (string) $form['training_end_time'] . ' -' . $form['lunch_break_time'] . ' minutes');
		if ($workEndSec !== false) {
			$form['work_time'] = $this->diffTime(
				$reportDate . ' ' . (string) $form['training_start_time'],
				date('Y-m-d H:i:s', $workEndSec)
			);
		}

		return $form;
	}

	private function resolveMode(int $loginAuth, array $form): string
	{
		if ($loginAuth === 0) {
			return ($form['admin_uuid'] ?? '') !== '' ? 'lock' : 'edit';
		}
		if (($form['report_uuid'] ?? '') === '') {
			return 'edit';
		}
		if (($form['admin_uuid'] ?? '') !== '') {
			return 'lock';
		}
		return 'comment';
	}

	private function isDate($value): bool
	{
		$s = (string) $value;
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) === 1 && strtotime($s . ' 00:00:00') !== false;
	}

	private function isTime($value): bool
	{
		return preg_match('/^\d{2}:\d{2}$/', (string) $value) === 1;
	}

	private function isOptionalTime($value): bool
	{
		$s = (string) $value;
		return $s === '' || $this->isTime($s);
	}

	private function isNumericRequired($value): bool
	{
		return preg_match('/^[0-9]+$/', (string) $value) === 1;
	}

	private function isMaxLength($value, int $max): bool
	{
		return mb_strlen((string) $value, $this->config->charset()) <= $max;
	}

	private function isMaxLineCount($value, int $max): bool
	{
		if ($value === '') {
			return true;
		}

		$lines = preg_split('/\r\n|\r|\n/', $value);
		return $lines !== false && count($lines) <= $max;
	}

	private function diffTime(string $from, string $to): string
	{
		$fromSec = strtotime($from);
		$toSec = strtotime($to);
		if ($fromSec === false || $toSec === false) {
			return '';
		}
		return gmdate('H時間i分', $toSec - $fromSec);
	}

	private function validateAdminAccess(array $form, string $loginAdminUuid): ?array
	{
		$userUuid = (string) ($form['user_uuid'] ?? '');
		if ($userUuid === '' || !$this->reportRepository->isUserManagedByAdmin($loginAdminUuid, $userUuid)) {
			return array('user_uuid' => '対象ユーザーが不正です。');
		}

		$reportUuid = (string) ($form['report_uuid'] ?? '');
		if ($reportUuid === '') {
			return null;
		}

		$report = $this->reportRepository->findReportById($reportUuid);
		if ($report === null) {
			return array('report_uuid' => '対象の日報が見つかりません。');
		}
		if ((string) $report['user_uuid'] !== $userUuid) {
			return array('report_uuid' => '対象の日報が不正です。');
		}

		return null;
	}
}
