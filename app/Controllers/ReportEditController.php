<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ReportRepository;
use App\Services\ReportFormService;
use App\Support\RequestContext;
use App\Support\ReportAuth;
use App\Support\ReportFieldLabels;
use App\Support\ViewRenderer;

final class ReportEditController
{
	private $auth;
	private $formService;
	private $reportRepository;
	private $viewRenderer;
	private $request;

	public function __construct(
		ReportAuth $auth,
		ReportFormService $formService,
		ReportRepository $reportRepository,
		ViewRenderer $viewRenderer,
		RequestContext $request
	) {
		$this->auth = $auth;
		$this->formService = $formService;
		$this->reportRepository = $reportRepository;
		$this->viewRenderer = $viewRenderer;
		$this->request = $request;
	}

	public function handle(): void
	{
		$this->auth->requireUserRoute();

		if ($this->request->isPost()) {
			\csrf_verify_or_abort($this->request);
			$result = $this->formService->validateAndStoreDraft(
				$this->request->allPost(),
				$this->auth->getLoginAuth(),
				$this->auth->getLoginId(),
				$this->auth->getLoginAdminUuid()
			);
			if ($result['ok']) {
				header('Location: report_confirm.php');
				exit;
			}

			$this->render($result['form'], $result['errors'], $result['mode']);
			return;
		}

		$reportUuid = ($value = $this->request->query('i')) !== null ? trim((string) $value) : null;
		$targetUserUuid = ($value = $this->request->query('user_uuid')) !== null ? trim((string) $value) : null;
		$targetReportDate = ($value = $this->request->query('report_date')) !== null ? trim((string) $value) : null;

		if ($this->shouldRedirectUserToDetail($reportUuid, $targetUserUuid, $targetReportDate)) {
			$currentReport = $this->reportRepository->findLatestReportByUserAndDate(
				$this->auth->getLoginId(),
				date('Y-m-d')
			);
			if ($currentReport !== null) {
				header('Location: report_detail.php?i=' . rawurlencode((string) $currentReport['report_uuid']));
				exit;
			}
		}

		$state = $this->formService->getFormState(
			$this->auth->getLoginAuth(),
			$this->auth->getLoginId(),
			$reportUuid,
			$targetUserUuid,
			$targetReportDate
		);

		if (($state['form']['user_uuid'] ?? '') !== '' && $this->auth->getLoginAuth() > 0) {
			$this->auth->resolveReportUserUuid((string) $state['form']['user_uuid']);
		}

		$this->render($state['form'], $state['errors'], $state['mode']);
	}

	private function shouldRedirectUserToDetail(?string $reportUuid, ?string $targetUserUuid, ?string $targetReportDate): bool
	{
		if ($this->auth->getLoginAuth() !== 0) {
			return false;
		}
		if (($reportUuid ?? '') !== '' || ($targetUserUuid ?? '') !== '' || ($targetReportDate ?? '') !== '') {
			return false;
		}

		$currentReport = $this->reportRepository->findLatestReportByUserAndDate(
			$this->auth->getLoginId(),
			date('Y-m-d')
		);
		if ($currentReport === null) {
			return false;
		}

		return trim((string) ($currentReport['charge_comment'] ?? '')) !== '';
	}

	private function render(array $form, array $errors, string $mode): void
	{
		$divMap = $this->reportRepository->findDivMap();
		$title = '日報入力';
		if ($this->auth->getLoginAuth() > 0 && $mode === 'edit') {
			$title = '日報登録';
		} elseif ($mode === 'comment') {
			$title = '日報確認';
		} elseif ($mode === 'lock') {
			$title = '日報確認';
		}

		$view = ($this->auth->getLoginAuth() === 0 && (int) ($form['visual_impairment_flg'] ?? 0) === 1)
			? 'report/edit_accessible'
			: 'report/edit';

		$this->viewRenderer->render($view, array(
			'title' => $title,
			'form' => $form,
			'errors' => $errors,
			'errorLabels' => ReportFieldLabels::forEdit(),
			'mode' => $mode,
			'divMap' => $divMap,
			'headerLinks' => $this->auth->buildHeaderLinks(),
			'loginAuth' => $this->auth->getLoginAuth(),
			'loginAdminId' => $this->auth->getLoginAdminId(),
		));
	}
}
