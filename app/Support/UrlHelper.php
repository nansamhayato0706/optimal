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

    public function assetVersion(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $root = $this->config->rootPath();
        $latest = 0;
        foreach (['css', 'js'] as $dir) {
            $files = glob($root . $dir . '/*.' . $dir);
            if (!$files) {
                continue;
            }
            foreach ($files as $file) {
                $mt = @filemtime($file);
                if ($mt !== false && $mt > $latest) {
                    $latest = $mt;
                }
            }
        }
        $cached = (string) ($latest > 0 ? $latest : time());
        return $cached;
    }
}
