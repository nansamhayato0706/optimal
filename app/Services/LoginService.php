<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Support\SessionStore;

final class LoginService
{
    private $adminRepository;
    private $session;

    public function __construct(AdminRepositoryInterface $adminRepository, SessionStore $session)
    {
        $this->adminRepository = $adminRepository;
        $this->session         = $session;
    }

    public function attempt(string $adminId, string $adminPassword): array
    {
        $admin = $this->adminRepository->findAdminByCredentials($adminId, $adminPassword);
        if ($admin === null) {
            return ['success' => false, 'message' => 'ログインIDまたはパスワードが異なります。'];
        }

        $adminDiv = (int) ($admin['admin_div'] ?? 0);
        if ($adminDiv <= 0) {
            return ['success' => false, 'message' => 'ログインできません。'];
        }

        $this->session->regenerate();
        $this->session->put('login.id',         (string) $admin['admin_uuid']);
        $this->session->put('login.auth',        $adminDiv);
        $this->session->put('login.group_id',    (string) $admin['group_uuid']);
        $this->session->put('login.admin_uuid',  (string) $admin['admin_uuid']);
        $this->session->put('login.admin_id',    (string) $admin['admin_id']);
        $this->session->put('login.admin_name',  (string) $admin['admin_name']);

        if ($adminDiv === 1) {
            $redirect = 'group.php';
        } elseif ($adminDiv === 2) {
            $redirect = 'admin.php';
        } else {
            $redirect = 'user.php';
        }

        return ['success' => true, 'redirect' => $redirect];
    }
}
