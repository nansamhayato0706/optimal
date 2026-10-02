<?php

declare(strict_types=1);

namespace App\Support;

final class ChatFileStorage
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'txt', 'csv',
        'mp3', 'wav', 'm4a', 'aac', 'ogg',
        'mp4', 'mov', 'm4v', 'webm',
    ];
    private const MAX_SIZE_BYTES = 300 * 1024 * 1024;

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
            error_log('[chat upload] rejected: invalid upload error=' . (string) ($file['error'] ?? 'n/a'));
            return null;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE_BYTES) {
            error_log('[chat upload] rejected: size=' . $size);
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
            error_log('[chat upload] rejected: extension=' . $extension);
            return null;
        }

        $dir = $this->config->chatFileDir() . $userUuid . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log('[chat upload] rejected: mkdir failed dir=' . $dir);
            return null;
        }

        $storedName = Uuid::v4() . '.' . $extension;
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . $storedName)) {
            error_log('[chat upload] rejected: move_uploaded_file failed dir=' . $dir);
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
