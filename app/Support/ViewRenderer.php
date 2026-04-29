<?php

declare(strict_types=1);

namespace App\Support;

final class ViewRenderer
{
    private $urlHelper;
    private $config;

    public function __construct(UrlHelper $urlHelper, AppConfig $config)
    {
        $this->urlHelper = $urlHelper;
        $this->config = $config;
    }

    public function render(string $template, array $data = []): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        $cssBase = rtrim($this->urlHelper->cssBase(), '/') . '/';
        $jsBase = rtrim($this->urlHelper->jsBase(), '/') . '/';
        $imgBase = rtrim($this->urlHelper->imgBase(), '/') . '/';

        $data += [
            'assetBase' => ['css' => $cssBase, 'js' => $jsBase, 'img' => $imgBase],
            'linkBase' => './',
            'cssBase' => $cssBase,
            'jsBase' => $jsBase,
            'imgBase' => $imgBase,
            'dummyImage' => $this->config->dummyImage(),
            'appConfig' => $this->config,
            'groupLabel' => $this->config->groupLabel(),
        ];

        extract($data, EXTR_SKIP);
        require $viewFile;
    }
}
