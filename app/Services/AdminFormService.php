<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Support\SessionStore;

final class AdminFormService
{
    private const SESSION_KEY = 'admin_modern_input';

    private $adminRepository;
    private $session;

    public function __construct(AdminRepositoryInterface $adminRepository, SessionStore $session)
    {
        $this->adminRepository = $adminRepository;
        $this->session = $session;
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function getDraft(): ?array
    {
        $value = $this->session->get(self::SESSION_KEY);
        return is_array($value) ? $value : null;
    }

    public function store(array $form): void
    {
        $this->session->put(self::SESSION_KEY, $form);
    }

    public function defaults(string $groupUuid): array
    {
        return [
            'admin_uuid' => '',
            'group_uuid' => $groupUuid,
            'admin_id' => '',
            'admin_password' => '',
            'admin_div' => '',
            'admin_name' => '',
            'admin_name_kana' => '',
            'admin_tel' => '',
            'admin_email' => '',
            'remark' => '',
        ];
    }

    public function normalize(array $input, string $groupUuid): array
    {
        $form = $this->defaults($groupUuid);
        foreach ($form as $key => $default) {
            if (array_key_exists($key, $input)) {
                $form[$key] = trim((string) $input[$key]);
            }
        }
        if ($form['group_uuid'] === '') {
            $form['group_uuid'] = $groupUuid;
        }

        return $form;
    }

    public function validate(array $form): array
    {
        $errors = [];
        foreach (['admin_id', 'admin_password', 'admin_name', 'admin_name_kana', 'admin_tel'] as $field) {
            if ((string) ($form[$field] ?? '') === '') {
                $errors[$field] = '必須入力項目です。';
            }
        }
        if ((string) ($form['admin_id'] ?? '') !== '' && !preg_match('/^[a-zA-Z0-9]+$/', (string) $form['admin_id'])) {
            $errors['admin_id'] = '半角英数で入力してください。';
        }
        if ((string) ($form['admin_password'] ?? '') !== '' && !preg_match('/^[a-zA-Z0-9]+$/', (string) $form['admin_password'])) {
            $errors['admin_password'] = '半角英数で入力してください。';
        }
        if ((string) ($form['admin_div'] ?? '') === '') {
            $errors['admin_div'] = '選択してください。';
        } elseif (!ctype_digit((string) $form['admin_div'])) {
            $errors['admin_div'] = '数値で入力してください。';
        }
        if ((string) ($form['admin_tel'] ?? '') !== '' && !preg_match('/^\d{10,11}$/', preg_replace('/\D/', '', (string) $form['admin_tel']))) {
            $errors['admin_tel'] = '電話番号の形式が正しくありません。';
        }
        if ((string) ($form['admin_email'] ?? '') !== '' && filter_var((string) $form['admin_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['admin_email'] = 'メールアドレスの形式が正しくありません。';
        }
        if ($this->adminRepository->adminIdExists((string) ($form['admin_id'] ?? ''), (string) ($form['admin_uuid'] ?? ''))) {
            $errors['admin_id'] = '既に使用されています。';
        }

        return $errors;
    }

    public function findForEdit(string $groupUuid, ?string $adminUuid): array
    {
        $draft = $this->getDraft();
        if ($draft !== null) {
            return array_merge($this->defaults($groupUuid), $draft);
        }
        if ($adminUuid === null || $adminUuid === '') {
            return $this->defaults($groupUuid);
        }

        $admin = $this->adminRepository->findAdminByUuid($adminUuid);
        return $admin === null ? $this->defaults($groupUuid) : array_merge($this->defaults($groupUuid), $admin);
    }
}
