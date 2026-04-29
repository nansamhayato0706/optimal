<?php

declare(strict_types=1);

namespace App\Support;

final class Uuid
{
    public static function v4(): string
    {
        return bin2hex(random_bytes(16));
    }
}
