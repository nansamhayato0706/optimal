<?php

declare(strict_types=1);

namespace App\Views;

/**
 * 自動エスケープ済み文字列。
 * __toString() で HTML エスケープ済みの値を返す。
 * raw() で生の値を取得できる（View で意図的に生 HTML を出力したい場合のみ使う）。
 */
final class SafeString
{
    private $raw;
    private $escaped;

    public function __construct(string $value)
    {
        $this->raw     = $value;
        $this->escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function __toString(): string
    {
        return $this->escaped;
    }

    public function raw(): string
    {
        return $this->raw;
    }
}
