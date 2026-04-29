<?php

declare(strict_types=1);

namespace App\Support;

final class JsonResponder
{
    /**
     * JSON レスポンスを送信して終了する
     *
     * @param array $payload
     * @param int   $status  HTTP ステータスコード
     */
    public static function send(array $payload, int $status = 200): void
    {
        if ($status !== 200) {
            http_response_code($status);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * エラー用 JSON レスポンスを送信して終了する
     */
    public static function error(string $message, int $status = 400): void
    {
        self::send(array('error' => $message), $status);
    }
}
