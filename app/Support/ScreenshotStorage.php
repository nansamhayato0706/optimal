<?php

declare(strict_types=1);

namespace App\Support;

final class ScreenshotStorage
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $file $_FILES['file'] 相当の1件分
     * @return string|null 保存先の相対パス（screenshotDir() からの相対）。失敗時は null
     */
    public function store(array $file, string $userUuid, string $requestUuid): ?string
    {
        if (!isset($file['tmp_name'])
            || (int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) $file['tmp_name'])) {
            return null;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE_BYTES) {
            return null;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        $relativeDir = $userUuid . '/';
        $dir = $this->config->screenshotDir() . $relativeDir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $relativePath = $relativeDir . $requestUuid . '.' . $extension;
        if (!move_uploaded_file((string) $file['tmp_name'], $this->config->screenshotDir() . $relativePath)) {
            return null;
        }

        return $relativePath;
    }

    public function absolutePath(string $relativePath): string
    {
        return $this->config->screenshotDir() . $relativePath;
    }
}
