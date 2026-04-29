<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LogService;
use App\Support\RequestContext;
use App\Auth\UserAdminAuth;
use App\Support\ViewRenderer;

final class LogIndexController
{
	private $auth;
	private $logService;
	private $request;
	private $viewRenderer;

	public function __construct(UserAdminAuth $auth, LogService $logService, RequestContext $request, ViewRenderer $viewRenderer)
	{
		$this->auth = $auth;
		$this->logService = $logService;
		$this->request = $request;
		$this->viewRenderer = $viewRenderer;
	}

	public function handle(): void
	{
		$this->auth->requireUserAdminRoute();
		$userParam = trim((string) $this->request->query('i', ''));
		$userUuid = $this->auth->resolveCurrentUserUuid($userParam !== '' ? $userParam : null);
		if ($userUuid === '') {
			$this->redirect('error.php');
		}

		$dateSt = trim((string) $this->request->post('date_st', '')) !== '' ? trim((string) $this->request->post('date_st', '')) : null;
		$dateEd = trim((string) $this->request->post('date_ed', '')) !== '' ? trim((string) $this->request->post('date_ed', '')) : null;
		$page = $this->logService->buildPageData($userUuid, $dateSt, $dateEd);

		if ($this->request->isPost()) {
			if (trim((string) $this->request->post('contact', '')) !== '') {
				$this->outputCsv($this->logService->buildContactCsv($page['contact']), 'contact_' . date('YmdHis') . '.csv');
			}
			if (trim((string) $this->request->post('log', '')) !== '') {
				$this->outputCsv($this->logService->buildSendCsv($page['send']), 'log_' . date('YmdHis') . '.csv');
			}
		}

		$this->viewRenderer->render('log/index', array(
			'title' => 'ログ一覧',
			'userName' => $page['user_name'],
			'dateSt' => $page['date_st'],
			'dateEd' => $page['date_ed'],
			'contacts' => $page['contact'],
			'sends' => $page['send'],
			'divMap' => $page['divMap'],
			'headerLinks' => $this->auth->buildHeaderLinks(),
			'loginAdminId' => $this->auth->getLoginAdminId(),
		));
	}

	private function outputCsv(string $csv, string $fileName): void
	{
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $fileName . ';');
		echo $csv;
		exit;
	}

	private function redirect(string $path): void
	{
		header('Location: ' . $path);
		exit;
	}
}
