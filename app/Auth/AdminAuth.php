<?php

declare(strict_types=1);

namespace App\Auth;

use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Support\AppConfig;
use App\Support\SessionStore;

final class AdminAuth
{
    private $adminRepository;
    private $config;
    private $session;

    public function __construct(AdminRepositoryInterface $adminRepository, AppConfig $config, SessionStore $session)
    {
        $this->adminRepository = $adminRepository;
        $this->config = $config;
        $this->session = $session;
    }

    public function requireAdminRoute(): void
    {
        if ($this->getLoginId() === '') {
            $this->redirect('error.php');
        }
        if (!in_array($this->getLoginAuth(), [1, 2], true)) {
            $this->redirect('login.php');
        }
    }

    public function resolveCurrentGroup(?string $requestedGroupUuid): string
    {
        if ($this->getLoginAuth() === 1 && $requestedGroupUuid !== null && $requestedGroupUuid !== '') {
            if ($this->adminRepository->groupExists($requestedGroupUuid)) {
                $this->session->put('login.group_id', $requestedGroupUuid);
            } else {
                $this->redirect('login.php');
            }
        }

        return $this->getLoginGroupId();
    }

    public function getLoginAuth(): int
    {
        return (int) $this->session->get('login.auth', 0);
    }

    public function getLoginId(): string
    {
        return (string) $this->session->get('login.id', '');
    }

    public function getLoginGroupId(): string
    {
        return (string) $this->session->get('login.group_id', '');
    }

    public function getLoginAdminId(): string
    {
        return (string) $this->session->get('login.admin_id', '');
    }

    public function getLoginAdminUuid(): string
    {
        return (string) $this->session->get('login.admin_uuid', '');
    }

    public function buildHeaderLinks(): array
    {
        $links = [];
        if ($this->getLoginAuth() === 1) {
            $links[] = ['link' => 'group.php', 'text' => $this->config->groupLabel() . '一覧'];
        }
        $links[] = ['link' => 'notice.php', 'text' => 'お知らせ'];
        $links[] = ['link' => 'link.php', 'text' => '外部リンク一覧'];
        $links[] = ['link' => 'admin.php', 'text' => '管理者一覧'];
        $links[] = ['link' => 'admin_edit.php', 'text' => '管理者登録'];
        $links[] = ['link' => 'login.php', 'text' => 'ログアウト'];

        return $links;
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
