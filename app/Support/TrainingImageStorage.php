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

    public function store(array $files, string $userUuid, int $eventType): void
    {
        if (!isset($files['file']['tmp_name']) || !is_uploaded_file($files['file']['tmp_name'])) {
            return;
        }

        $originalName = (string) ($files['file']['name'] ?? '');
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        $dir = $this->config->uploadDir() . $userUuid . '/' . date('Ymd') . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileName = date('His') . $eventType . '.' . $extension;
        move_uploaded_file($files['file']['tmp_name'], $dir . $fileName);
    }
}
