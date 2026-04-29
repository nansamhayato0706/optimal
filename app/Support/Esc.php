<?php

declare(strict_types=1);

namespace App\Support;

final class Esc
{
    public static function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
