<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AppConfig;
use App\Support\Logger;

/**
 * OpenAI の音声文字起こし API (/v1/audio/transcriptions) を呼び出す。
 * WPF クライアントの音声入力（コマンド認識・チャット自由入力）の裏側で使う。
 * API キーはこのクラスの外に一切渡さない。ログにも本文・キーを残さない。
 */
final class OpenAiTranscriptionService
{
    const API_URL         = 'https://api.openai.com/v1/audio/transcriptions';
    const REQUEST_TIMEOUT = 20;

    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    public function isEnabled(): bool
    {
        return $this->config->openAiTranscriptionEnabled();
    }

    /**
     * @param string $tmpPath アップロードされた一時ファイルの絶対パス（move_uploaded_file 済み、または is_uploaded_file 検証済みのパス）
     * @param string $originalName クライアントが送ってきたファイル名（拡張子判定用）
     * @return string|null 文字起こし結果。失敗時は null
     */
    public function transcribe(string $tmpPath, string $originalName): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }
        if (!is_file($tmpPath)) {
            return null;
        }

        $ch = curl_init(self::API_URL);
        if ($ch === false) {
            return null;
        }

        $mimeType = 'audio/wav';
        $fileName = $originalName !== '' ? $originalName : 'audio.wav';

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config->openAiApiKey(),
            ],
            CURLOPT_POSTFIELDS     => [
                'file'            => new \CURLFile($tmpPath, $mimeType, $fileName),
                'model'           => $this->config->openAiTranscribeModel(),
                'language'        => 'ja',
                'response_format' => 'json',
            ],
            CURLOPT_TIMEOUT        => self::REQUEST_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::REQUEST_TIMEOUT,
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code !== 200) {
            // キー・音声内容・レスポンス本文はログに残さない。http_code のみ記録する。
            Logger::warning('openai transcription failed', ['http_code' => $code, 'curl_err' => $err !== '' ? $err : null]);
            return null;
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded) || !isset($decoded['text']) || !is_string($decoded['text'])) {
            Logger::warning('openai transcription unexpected response shape', ['http_code' => $code]);
            return null;
        }

        return trim($decoded['text']);
    }
}
