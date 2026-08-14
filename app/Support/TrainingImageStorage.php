<?php

declare(strict_types=1);

namespace App\Support;

final class TrainingImageStorage
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'bmp'];

    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    public function store(array $files, string $userUuid, int $eventType): bool
    {
        if (!isset($files['file']['tmp_name'])
            || (int) ($files['file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK
            || !is_uploaded_file($files['file']['tmp_name'])) {
            return false;
        }

        $originalName = (string) ($files['file']['name'] ?? '');
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        $dir = $this->config->uploadDir() . $userUuid . '/' . date('Ymd') . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $fileName = date('His') . $eventType . '.' . $extension;
        return move_uploaded_file($files['file']['tmp_name'], $dir . $fileName);
    }
}
