<?php

declare(strict_types=1);

namespace App\Support;

final class SessionStore
{
    /** @param array<int,string>|string $key */
    public function get($key, $default = null)
    {
        $segments = $this->segments($key);
        $current  = $_SESSION;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }
        return $current;
    }

    /** @param array<int,string>|string $key */
    public function put($key, $value): void
    {
        $segments = $this->segments($key);
        if ($segments === []) {
            return;
        }
        $current   = &$_SESSION;
        $lastIndex = count($segments) - 1;
        foreach ($segments as $index => $segment) {
            if ($index === $lastIndex) {
                $current[$segment] = $value;
                return;
            }
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
    }

    /** @param array<int,string>|string $key */
    public function forget($key): void
    {
        $segments = $this->segments($key);
        if ($segments === []) {
            return;
        }
        $current   = &$_SESSION;
        $lastIndex = count($segments) - 1;
        foreach ($segments as $index => $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return;
            }
            if ($index === $lastIndex) {
                unset($current[$segment]);
                return;
            }
            $current = &$current[$segment];
        }
    }

    /** @param array<int,string>|string $key */
    public function has($key): bool
    {
        return $this->get($key) !== null;
    }

    public function clear(): void
    {
        $_SESSION = [];
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    /** @return array<int,string> */
    private function segments($key): array
    {
        if (is_array($key)) {
            return array_values(array_filter(array_map('strval', $key), static function ($v): bool {
                return $v !== '';
            }));
        }
        return array_values(array_filter(explode('.', (string) $key), static function ($v): bool {
            return $v !== '';
        }));
    }
}
