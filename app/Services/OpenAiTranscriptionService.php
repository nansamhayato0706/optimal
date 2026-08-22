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

    /**
     * コマンド認識モード時に渡すヒント。短い孤立した単語（「ログアウト」等）は文脈が無いため
     * 誤認識されやすい（実運用で「Lautof.」「ツーアウト」等に誤認識される例が確認された）。
     * OpenAI の prompt パラメータで語彙を事前に示すことで認識精度を上げる。
     * WPF 側の SpeechService.CommandPhrases と同じ語彙を保つこと。
     */
    const COMMAND_VOCABULARY_PROMPT =
        'よくある発話：ログイン、ログアウト、ログオフ、作業開始、開始、スタート、作業終了、終了、ストップ、' .
        '送信、送信する、更新、更新する、連絡要求、連絡、問い合わせ、問い合せ、緊急、ヘルプ、助けて、' .
        'はい、イエス、オーケー、いいえ、ノー、キャンセル、' .
        '日報、日報を開く、レポート、リポート、レポートを開く、報告、チャット、チャット入力、取り消し、閉じる';

    /**
     * チャット口述（自由入力）中に渡すヒント。内容そのものは誘導したくないので、
     * 口述を終える制御語「送信」「取り消し」だけを軽く示す。
     */
    const DICTATION_CONTROL_PROMPT = '発話の終わりに「送信」または「取り消し」と言うことがあります。';

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
     * @param string $mode 'command'：コマンド語彙全体をヒントにする。'dictation'：チャット口述中。
     *   内容を誘導しないよう「送信」「取り消し」の制御語のみヒントにする。それ以外：ヒント無し。
     * @return string|null 文字起こし結果。失敗時は null
     */
    public function transcribe(string $tmpPath, string $originalName, string $mode = ''): ?string
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

        $postFields = [
            'file'            => new \CURLFile($tmpPath, $mimeType, $fileName),
            'model'           => $this->config->openAiTranscribeModel(),
            'language'        => 'ja',
            'response_format' => 'json',
        ];
        if ($mode === 'command') {
            $postFields['prompt'] = self::COMMAND_VOCABULARY_PROMPT;
        } elseif ($mode === 'dictation') {
            $postFields['prompt'] = self::DICTATION_CONTROL_PROMPT;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config->openAiApiKey(),
            ],
            CURLOPT_POSTFIELDS     => $postFields,
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
