<?php

declare(strict_types=1);

namespace App\Views;

use App\Support\AppConfig;
use App\Support\UrlHelper;

final class View
{
    private $viewsDir;
    private $urlHelper;
    private $config;

    public function __construct(string $viewsDir, UrlHelper $urlHelper, AppConfig $config)
    {
        $this->viewsDir  = rtrim($viewsDir, '/\\');
        $this->urlHelper = $urlHelper;
        $this->config    = $config;
    }

    public function render(string $template, array $data = []): void
    {
        $viewFile = $this->viewsDir . '/' . $template . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
            return;
        }

        $cssBase = $this->urlHelper->cssBase() . '/';
        $jsBase  = $this->urlHelper->jsBase()  . '/';
        $imgBase = $this->urlHelper->imgBase()  . '/';

        $data['cssBase']      = $cssBase;
        $data['jsBase']       = $jsBase;
        $data['imgBase']      = $imgBase;
        $data['dummyImage']   = $this->config->dummyImage();
        $data['assetBase']    = ['css' => $cssBase, 'js' => $jsBase, 'img' => $imgBase];
        $data['_config']      = $this->config;
        $data['_csrf_field']  = csrf_field();
        $data['groupLabel']   = $this->config->groupLabel();

        // XSS 防止用のエスケープヘルパーを全 View に注入
        $data['h'] = static function ($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        extract($data, EXTR_SKIP);
        require $viewFile;
    }
}
