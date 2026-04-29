<?php

declare(strict_types=1);

namespace App\Support;

final class TrainingResponses
{
    public static function loginFailed(): array
    {
        return ['res' => '0'];
    }

    public static function loginSuccess(string $token, array $user): array
    {
        return [
            'res' => $token,
            's'   => (string) ($user['send_interval']     ?? ''),
            'k'   => (string) ($user['keyboard_interval'] ?? ''),
            'm'   => (string) ($user['mouse_interval']    ?? ''),
        ];
    }

    public static function eventFailed(): array
    {
        return ['res' => false];
    }

    public static function tokenResult(string $token): array
    {
        return ['res' => $token];
    }
}
