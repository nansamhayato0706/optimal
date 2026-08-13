<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Support\SessionStore;

final class GroupFormService
{
    private const SESSION_KEY = 'group_modern_input';

    private $groupRepository;
    private $session;

    public function __construct(GroupRepositoryInterface $groupRepository, SessionStore $session)
    {
        $this->groupRepository = $groupRepository;
        $this->session         = $session;
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function getDraft(): array
    {
        $value = $this->session->get(self::SESSION_KEY, []);
        return is_array($value) ? $value : [];
    }

    public function store(array $form): void
    {
        $this->session->put(self::SESSION_KEY, $form);
    }

    public function defaults(): array
    {
        return [
            'group_uuid'           => '',
            'group_name'           => '',
            'group_name_kana'      => '',
            'group_zip_code'       => '',
            'group_prefecture_div' => '',
            'group_address'        => '',
            'group_tel'            => '',
            'group_email'          => '',
            'notify_slack_webhook_url' => '',
            'notification'         => '',
            'remark'               => '',
        ];
    }

    public function normalize(array $input): array
    {
        return [
            'group_uuid'           => trim((string) ($input['group_uuid'] ?? '')),
            'group_name'           => trim((string) ($input['group_name'] ?? '')),
            'group_name_kana'      => trim((string) ($input['group_name_kana'] ?? '')),
            'group_zip_code'       => trim((string) ($input['group_zip_code'] ?? '')),
            'group_prefecture_div' => trim((string) ($input['group_prefecture_div'] ?? '')),
            'group_address'        => trim((string) ($input['group_address'] ?? '')),
            'group_tel'            => trim((string) ($input['group_tel'] ?? '')),
            'group_email'          => trim((string) ($input['group_email'] ?? '')),
            'notify_slack_webhook_url' => trim((string) ($input['notify_slack_webhook_url'] ?? '')),
            'notification'         => trim((string) ($input['notification'] ?? '')),
            'remark'               => trim((string) ($input['remark'] ?? '')),
        ];
    }

    public function validate(array $form): array
    {
        $errors = [];
        foreach (['group_name', 'group_name_kana', 'group_zip_code', 'group_prefecture_div', 'group_address', 'group_tel', 'group_email'] as $field) {
            if ((string) $form[$field] === '') {
                $errors[$field] = '必須項目です。';
            }
        }
        if ((string) $form['group_zip_code'] !== '' && !preg_match('/^\d{3}\-?\d{4}$/', (string) $form['group_zip_code'])) {
            $errors['group_zip_code'] = '郵便番号の形式が不正です。';
        }
        if ((string) $form['group_prefecture_div'] !== '' && !ctype_digit((string) $form['group_prefecture_div'])) {
            $errors['group_prefecture_div'] = '都道府県が不正です。';
        }
        if ((string) $form['group_tel'] !== '' && !preg_match('/^\d{10,11}$/', preg_replace('/\D/', '', (string) $form['group_tel']))) {
            $errors['group_tel'] = '電話番号の形式が不正です。';
        }
        if ((string) $form['group_email'] !== '' && filter_var((string) $form['group_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['group_email'] = 'e-mailの形式が不正です。';
        }
        if ((string) $form['notify_slack_webhook_url'] !== '' && strpos((string) $form['notify_slack_webhook_url'], 'https://hooks.slack.com/') !== 0) {
            $errors['notify_slack_webhook_url'] = 'Slack Webhook URLは https://hooks.slack.com/ で始まる必要があります。';
        }
        foreach (['group_name', 'group_name_kana', 'group_address', 'group_email', 'notify_slack_webhook_url', 'notification', 'remark'] as $field) {
            if (mb_strlen((string) $form[$field]) > 255) {
                $errors[$field] = '255文字以内で入力してください。';
            }
        }
        return $errors;
    }

    public function findForEdit(?string $groupUuid): array
    {
        $draft = $this->getDraft();
        if ($draft !== []) {
            return array_merge($this->defaults(), $draft);
        }
        if ($groupUuid === null || $groupUuid === '') {
            return $this->defaults();
        }
        $group = $this->groupRepository->findGroupByUuid($groupUuid);
        return $group === null ? $this->defaults() : array_merge($this->defaults(), $group);
    }
}
