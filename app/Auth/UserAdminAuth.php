<?php

declare(strict_types=1);

namespace App\Auth;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\AppConfig;
use App\Support\SessionStore;

final class UserAdminAuth
{
    private $userRepository;
    private $config;
    private $session;

    public function __construct(
        UserRepositoryInterface $userRepository,
        AppConfig $config,
        SessionStore $session
    ) {
        $this->userRepository = $userRepository;
        $this->config         = $config;
        $this->session        = $session;
    }

    public function requireUserAdminRoute(): void
    {
        if ($this->getLoginAdminUuid() === '' || !in_array($this->getLoginAuth(), [1, 2, 3], true)) {
            header('Location: login.php');
            exit;
        }
    }

    public function resolveCurrentAdminUuid(?string $requestedAdminUuid): string
    {
        $adminUuid = $this->getLoginAdminUuid();
        if ($this->getLoginAuth() === 1 && $requestedAdminUuid !== null && $requestedAdminUuid !== '') {
            $adminUuid = $requestedAdminUuid;
        }

        $groupUuid = $this->userRepository->findActiveAdminGroupUuid($adminUuid);
        if ($groupUuid === '') {
            header('Location: login.php');
            exit;
        }

        // 管理者一覧から別事業所の管理者を選んだとき、以前の事業所IDで
        // 利用者を絞り込んで0件になるのを防ぐ。
        $this->session->put('login.admin_uuid', $adminUuid);
        $this->session->put('login.group_id', $groupUuid);

        return $adminUuid;
    }

    public function resolveCurrentUserUuid(?string $requestedUserUuid): string
    {
        if ($requestedUserUuid !== null && $requestedUserUuid !== '') {
            if (!$this->userRepository->userAssignedToAdmin($requestedUserUuid, $this->getLoginAdminUuid())) {
                header('Location: error.php');
                exit;
            }
            $this->session->put('login.user_id', $requestedUserUuid);
        }

        return $this->getLoginUserUuid();
    }

    public function getLoginAdminUuid(): string
    {
        return (string) $this->session->get('login.admin_uuid', '');
    }

    public function getLoginAdminId(): string
    {
        return (string) $this->session->get('login.admin_id', '');
    }

    public function getLoginAdminName(): string
    {
        return (string) $this->session->get('login.admin_name', '');
    }

    public function getLoginGroupUuid(): string
    {
        return (string) $this->session->get('login.group_id', '');
    }

    public function getLoginGroupId(): string
    {
        return $this->getLoginGroupUuid();
    }

    public function getLoginAuth(): int
    {
        return (int) $this->session->get('login.auth', 0);
    }

    public function getDeleteFlag(): string
    {
        return (string) $this->session->get('login.delete_flg', '0');
    }

    public function setDeleteFlag(string $flag): void
    {
        $this->session->put('login.delete_flg', $flag);
    }

    public function buildHeaderLinks(): array
    {
        $links = [];
        if ($this->getLoginAuth() === 1) {
            $links[] = ['link' => 'group.php', 'text' => $this->config->groupLabel() . '一覧'];
        }
        if ($this->getLoginAuth() === 1 || $this->getLoginAuth() === 2) {
            $links[] = ['link' => 'admin.php', 'text' => '管理者一覧'];
        }
        $links[] = ['link' => 'notice.php', 'text' => 'お知らせ'];
        $links[] = ['link' => 'link.php', 'text' => '外部リンク一覧'];
        $links[] = ['link' => 'user.php', 'text' => 'ユーザー一覧'];
        $links[] = ['link' => 'report_daily.php', 'text' => '日別日報一覧'];
        $links[] = ['link' => 'user_edit.php', 'text' => 'ユーザー登録'];
        $links[] = ['link' => 'login.php', 'text' => 'ログアウト'];
        return $links;
    }

    public function getLoginUserUuid(): string
    {
        return (string) $this->session->get('login.user_id', '');
    }

}
