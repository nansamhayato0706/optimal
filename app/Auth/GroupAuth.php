<?php

declare(strict_types=1);

namespace App\Auth;

use App\Support\AppConfig;
use App\Support\SessionStore;

final class GroupAuth
{
    private $config;
    private $session;

    public function __construct(AppConfig $config, SessionStore $session)
    {
        $this->config  = $config;
        $this->session = $session;
    }

    public function requireGroupRoute(): void
    {
        if ($this->getLoginId() === '') {
            header('Location: error.php');
            exit;
        }
        if ($this->getLoginAuth() !== 1) {
            header('Location: login.php');
            exit;
        }
    }

    public function getLoginAuth(): int    { return (int)    $this->session->get('login.auth', 0); }
    public function getLoginId(): string   { return (string) $this->session->get('login.id', ''); }
    public function getLoginAdminId(): string   { return (string) $this->session->get('login.admin_id', ''); }
    public function getLoginAdminUuid(): string { return (string) $this->session->get('login.admin_uuid', ''); }

    public function buildHeaderLinks(): array
    {
        $label = $this->config->groupLabel();
        return [
            ['link' => 'group.php',      'text' => $label . '一覧'],
            ['link' => 'group_edit.php', 'text' => $label . '登録'],
            ['link' => 'login.php',      'text' => 'ログアウト'],
        ];
    }
}
