<?php

declare(strict_types=1);

namespace App\Support;

final class UrlHelper
{
    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    public function cssBase(): string { return $this->config->appUrl() . '/css'; }
    public function jsBase(): string  { return $this->config->appUrl() . '/js'; }
    public function imgBase(): string { return $this->config->appUrl() . '/img'; }
    public function to(string $path): string
    {
        return $this->config->appUrl() . '/' . ltrim($path, '/');
    }
}
