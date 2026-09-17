<?php

declare(strict_types=1);

namespace App\Support;

final class ChatFileStorage
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'txt', 'csv',
    ];
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $file $_FILES['file'] 相当の1件分
     * @param string $originalName 送信元での元ファイル名。非ASCII文字を含むマルチパートのfilenameは
     *                              クライアント側の実装差でサーバーが正しく解釈できないことがあるため、
     *                              呼び出し側から別フィールドで渡されたものを優先して使う。
     * @return array{stored_name: string, original_name: string, url: string}|null
     */
    public function store(array $file, string $userUuid, string $originalName = ''): ?array
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

        if ($originalName === '') {
            $originalName = (string) ($file['name'] ?? '');
        }
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        }
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        $dir = $this->config->chatFileDir() . $userUuid . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $storedName = Uuid::v4() . '.' . $extension;
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . $storedName)) {
            return null;
        }

        return [
            'stored_name'   => $storedName,
            'original_name' => $originalName,
            'url'           => $this->config->chatFileBase() . rawurlencode($userUuid) . '/' . rawurlencode($storedName),
        ];
    }

    /**
     * @param array{stored_name: string, original_name: string, url: string} $stored
     */
    public function buildMessageText(array $stored): string
    {
        return $stored['url'];
    }

    /**
     * chat_text がこのユーザーのファイル送信URLそのものであれば、対応する実ファイルを削除する。
     * URL形式でない・他ユーザーのファイル・パス直書き等はすべて無視し、何もしない。
     */
    public function deleteIfChatFileUrl(string $chatText, string $userUuid): void
    {
        $base = $this->config->chatFileBase();
        if ($base === '' || strpos($chatText, $base) !== 0) {
            return;
        }

        $relative = substr($chatText, strlen($base));
        $parts = explode('/', $relative);
        if (count($parts) !== 2) {
            return;
        }

        [$encodedUuid, $encodedName] = $parts;
        if (rawurldecode($encodedUuid) !== $userUuid) {
            return;
        }

        $storedName = rawurldecode($encodedName);
        if ($storedName === '' || $storedName !== basename($storedName)) {
            return;
        }

        $path = $this->config->chatFileDir() . $userUuid . '/' . $storedName;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
