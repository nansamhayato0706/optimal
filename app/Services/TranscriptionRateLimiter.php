<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AppConfig;

/**
 * WPF 音声入力の暴走を止めるための保険。
 * 音声入力の利用者は最大 5 名程度を想定しており、費用そのものは制約にならない。
 * ここで守りたいのは「マイクが挿しっぱなしで騒がしい部屋に放置された」ような
 * 事故が際限なく OpenAI API を呼び続けないようにすること。
 *
 * ユーザーごとに当日の累計音声秒数をファイルに記録し、上限を超えたら 429 を返させる。
 * 利用者が少数なため DB テーブルは追加せず、storage/transcribe/ 配下の JSON ファイルで足りる。
 */
final class TranscriptionRateLimiter
{
    const MIN_INTERVAL_SECONDS = 1.0;

    private $config;
    private $storageDir;

    public function __construct(AppConfig $config)
    {
        $this->config    = $config;
        $this->storageDir = rtrim($config->rootPath(), '/') . '/storage/transcribe';
    }

    /**
     * @return array{allowed:bool, reason:?string}
     */
    public function consume(string $userUuid, float $audioSeconds): array
    {
        if ($userUuid === '') {
            return ['allowed' => false, 'reason' => 'invalid_user'];
        }
        if (!$this->ensureStorageDir()) {
            // ディレクトリを用意できない場合は制限をかけようがないため、安全側に倒して拒否する。
            return ['allowed' => false, 'reason' => 'storage_unavailable'];
        }

        $path = $this->pathFor($userUuid);
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return ['allowed' => false, 'reason' => 'storage_unavailable'];
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return ['allowed' => false, 'reason' => 'storage_unavailable'];
            }

            $raw = stream_get_contents($handle);
            $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (!is_array($state)) {
                $state = [];
            }

            $today = date('Y-m-d');
            $usedSeconds = ($state['date'] ?? '') === $today ? (float) ($state['used_seconds'] ?? 0) : 0.0;
            $lastRequestAt = (float) ($state['last_request_at'] ?? 0);

            $now = microtime(true);
            if ($lastRequestAt > 0 && ($now - $lastRequestAt) < self::MIN_INTERVAL_SECONDS) {
                return ['allowed' => false, 'reason' => 'too_frequent'];
            }

            $dailyLimit = $this->config->transcribeDailySeconds();
            if ($usedSeconds + $audioSeconds > $dailyLimit) {
                return ['allowed' => false, 'reason' => 'daily_limit_exceeded'];
            }

            $state = [
                'date'             => $today,
                'used_seconds'     => $usedSeconds + $audioSeconds,
                'last_request_at'  => $now,
            ];

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($state, JSON_UNESCAPED_UNICODE));
            fflush($handle);

            return ['allowed' => true, 'reason' => null];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function pathFor(string $userUuid): string
    {
        return $this->storageDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $userUuid) . '.json';
    }

    private function ensureStorageDir(): bool
    {
        if (is_dir($this->storageDir) && is_writable($this->storageDir)) {
            return true;
        }
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0777, true);
        }
        return is_dir($this->storageDir) && is_writable($this->storageDir);
    }
}
