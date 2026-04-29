<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\SessionStore;

final class UserFormService
{
    private const SESSION_KEY = 'user_modern_input';

    private $userRepository;
    private $session;

    public function __construct(UserRepositoryInterface $userRepository, SessionStore $session)
    {
        $this->userRepository = $userRepository;
        $this->session = $session;
    }

    public function buildInitialState(string $groupUuid, string $loginAdminUuid, ?string $userUuid): array
    {
        $input = $this->session->get(self::SESSION_KEY);
        $this->session->forget(self::SESSION_KEY);
        if (is_array($input)) {
            return ['form' => array_merge($this->defaultForm($groupUuid), $input), 'errors' => []];
        }

        $form = $this->defaultForm($groupUuid);
        if ($userUuid !== null && $userUuid !== '') {
            $user = $this->userRepository->findUserByUuid($userUuid);
            if ($user !== null) {
                foreach ($form as $key => $default) {
                    if (array_key_exists($key, $user) && !is_array($default)) {
                        $form[$key] = (string) $user[$key];
                    }
                }
                $form['admin_uuid'] = $this->userRepository->findAssignedAdminUuids($userUuid);
            }
        } elseif ($this->userRepository->countActiveUsers($groupUuid) >= $this->maxUser()) {
            return [
                'form' => $form,
                'errors' => ['general' => 'ユーザー登録数が上限（' . $this->maxUser() . '人）に達しました。終了したユーザーを退避させてください。'],
            ];
        }

        return ['form' => $form, 'errors' => []];
    }

    public function validateAndStore(array $input, string $groupUuid): array
    {
        $form = $this->defaultForm($groupUuid);
        foreach ($form as $key => $default) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            if (is_array($default)) {
                $form[$key] = array_values(array_filter(array_map('strval', (array) $input[$key])));
            } else {
                $form[$key] = trim((string) $input[$key]);
            }
        }
        if ($form['group_uuid'] === '') {
            $form['group_uuid'] = $groupUuid;
        }

        $errors = $this->validate($form);
        if ($errors !== []) {
            return ['ok' => false, 'form' => $form, 'errors' => $errors];
        }

        $this->session->put(self::SESSION_KEY, $form);
        return ['ok' => true, 'form' => $form, 'errors' => []];
    }

    public function getStored(): ?array
    {
        $value = $this->session->get(self::SESSION_KEY);
        return is_array($value) ? $value : null;
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    private function validate(array $form): array
    {
        $errors = [];
        foreach (['user_id', 'user_password', 'user_name', 'user_name_kana', 'user_address', 'serial_no'] as $field) {
            if ((string) ($form[$field] ?? '') === '') {
                $errors[$field] = '必須入力項目です。';
            }
        }
        foreach (['user_div', 'work_style_div', 'delete_flg', 'visual_impairment_flg', 'sex_div', 'user_prefecture_div', 'send_interval', 'keyboard_interval', 'mouse_interval'] as $field) {
            if (!ctype_digit((string) ($form[$field] ?? ''))) {
                $errors[$field] = '数値で入力してください。';
            }
        }
        if ((string) ($form['user_id'] ?? '') !== '' && !preg_match('/^[a-zA-Z0-9]+$/', (string) $form['user_id'])) {
            $errors['user_id'] = '半角英数で入力してください。';
        }
        if ((string) ($form['user_password'] ?? '') !== '' && !preg_match('/^[a-zA-Z0-9]+$/', (string) $form['user_password'])) {
            $errors['user_password'] = '半角英数で入力してください。';
        }
        if ((string) ($form['birthday'] ?? '') !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $form['birthday'])) {
            $errors['birthday'] = '日付の形式が正しくありません。';
        }
        if ((string) ($form['user_zip_code'] ?? '') !== '' && !preg_match('/^\d{7}$/', (string) $form['user_zip_code'])) {
            $errors['user_zip_code'] = '郵便番号の形式が正しくありません。';
        }
        if ((string) ($form['user_tel'] ?? '') !== '' && !preg_match('/^\d{10,11}$/', (string) $form['user_tel'])) {
            $errors['user_tel'] = '電話番号の形式が正しくありません。';
        }
        if ((string) ($form['user_email'] ?? '') !== '' && filter_var((string) $form['user_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['user_email'] = 'メールアドレスの形式が正しくありません。';
        }
        if ($this->userRepository->userIdExists((string) ($form['user_id'] ?? ''), (string) ($form['user_uuid'] ?? ''))) {
            $errors['user_id'] = '既に使用されています。';
        }

        return $errors;
    }

    private function defaultForm(string $groupUuid): array
    {
        return [
            'user_uuid' => '',
            'group_uuid' => $groupUuid,
            'user_id' => '',
            'user_password' => '',
            'user_div' => '',
            'work_style_div' => '',
            'delete_flg' => '0',
            'visual_impairment_flg' => '0',
            'serial_no' => '',
            'user_name' => '',
            'user_name_kana' => '',
            'sex_div' => '',
            'birthday' => '',
            'user_zip_code' => '',
            'user_prefecture_div' => '',
            'user_address' => '',
            'user_tel' => '',
            'user_email' => '',
            'volume_no' => '',
            'send_interval' => '30',
            'keyboard_interval' => '30',
            'mouse_interval' => '30',
            'login_uuid' => '',
            'remark' => '',
            'admin_uuid' => [],
        ];
    }

    private function maxUser(): int
    {
        return defined('MAX_USER') ? (int) MAX_USER : 80;
    }
}
