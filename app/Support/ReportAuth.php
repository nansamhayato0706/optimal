<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\ReportRepository;

final class ReportAuth
{
	private $config;
	private $reportRepository;
	private $sessionStore;
	private $request;

	public function __construct(
		ReportRepository $reportRepository,
		AppConfig $config,
		SessionStore $sessionStore,
		RequestContext $request
	)
	{
		$this->config = $config;
		$this->reportRepository = $reportRepository;
		$this->sessionStore = $sessionStore;
		$this->request = $request;
	}

	public function requireUserRoute(): void
	{
		$this->loginByTokenIfPresent();

		if ($this->getLoginId() === '') {
			$this->redirect('error.php');
		}
	}

	public function getLoginAuth(): int
	{
		return (int) $this->sessionStore->get('login.auth', 0);
	}

	public function getLoginId(): string
	{
		return (string) $this->sessionStore->get('login.id', '');
	}

	public function getLoginUserId(): string
	{
		return (string) $this->sessionStore->get('login.user_id', '');
	}

	public function getLoginAdminUuid(): string
	{
		return (string) $this->sessionStore->get('login.admin_uuid', '');
	}

	public function getLoginAdminId(): string
	{
		return (string) $this->sessionStore->get('login.admin_id', '');
	}

	public function resolveReportUserUuid(?string $requestedUserUuid): string
	{
		if ($this->getLoginAuth() > 0) {
			if ($requestedUserUuid !== null && $requestedUserUuid !== '') {
				$canAccess = $this->reportRepository->isUserManagedByAdmin(
					$this->getLoginAdminUuid(),
					$requestedUserUuid
				);
				if (!$canAccess) {
					$this->redirect('error.php');
				}

				$this->sessionStore->put('login.user_id', $requestedUserUuid);
			}

			return $this->getLoginUserId();
		}

		return $this->getLoginId();
	}

	public function buildHeaderLinks(): array
	{
		$auth = $this->getLoginAuth();
		$links = array();

		if ($auth === 1) {
			$links[] = array('link' => 'group.php', 'text' => $this->config->groupLabel() . '一覧');
		}
		if ($auth === 1 || $auth === 2) {
			$links[] = array('link' => 'admin.php', 'text' => '管理者一覧');
		}
		if ($auth === 1 || $auth === 2 || $auth === 3) {
			$links[] = array('link' => 'user.php', 'text' => 'ユーザー一覧');
		}

		$links[] = array('link' => 'report.php', 'text' => '日報一覧');

		if ($auth === 1 || $auth === 2 || $auth === 3) {
			$links[] = array('link' => 'login.php', 'text' => 'ログアウト');
		}

		return $links;
	}

	private function loginByTokenIfPresent(): void
	{
		$token = trim((string) $this->request->query('t', ''));
		if ($token === '') {
			return;
		}

		$this->sessionStore->forget('login');
		$userUuid = $this->reportRepository->findUserUuidByLoginToken($token);
		if ($userUuid !== null) {
			$this->sessionStore->put('login.id', $userUuid);
			$this->sessionStore->put('login.auth', 0);
		}
	}

	private function redirect(string $path): void
	{
		header('Location: ' . $path);
		exit;
	}
}
