<?php

declare(strict_types=1);

namespace App\Support;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const FIELD_NAME  = '_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function fieldHtml(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . $token . '">';
    }

    public static function verifyRequest(RequestContext $request): bool
    {
        if (!$request->isPost()) {
            return true;
        }
        $submitted = $request->csrfToken();
        $stored    = (string) ($_SESSION[self::SESSION_KEY] ?? '');
        if ($submitted === '' || $stored === '') {
            return false;
        }
        return hash_equals($stored, $submitted);
    }
}
