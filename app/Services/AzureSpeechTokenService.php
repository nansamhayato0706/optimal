<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AppConfig;
use App\Support\Logger;

/**
 * Azure Speech の短期認可トークンを発行する。
 * Azure STS が返す JWT は 10 分有効。クライアントには 9 分（540 秒）で再取得させる。
 * サーバー側でも 8 分（480 秒）の薄いキャッシュを持ち、Azure STS への過剰な呼び出しを避ける。
 */
final class AzureSpeechTokenService
{
    const CLIENT_TTL_SECONDS = 540;
    const CACHE_TTL_SECONDS  = 480;
    const REQUEST_TIMEOUT    = 5;

    private $config;

    /** @var array{token:string,region:string,issued_at:int}|null */
    private static $cache = null;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return array{token:string,region:string,expires_in:int}|null
     */
    public function issueToken(): ?array
    {
        if (!$this->config->azureSpeechEnabled()) {
            return null;
        }

        $region = $this->config->azureSpeechRegion();
        $now    = time();

        if (self::$cache !== null
            && self::$cache['region'] === $region
            && ($now - self::$cache['issued_at']) < self::CACHE_TTL_SECONDS
        ) {
            return [
                'token'      => self::$cache['token'],
                'region'     => self::$cache['region'],
                'expires_in' => self::CLIENT_TTL_SECONDS,
            ];
        }

        $token = $this->fetchToken($region, $this->config->azureSpeechKey());
        if ($token === null) {
            return null;
        }

        self::$cache = [
            'token'     => $token,
            'region'    => $region,
            'issued_at' => $now,
        ];

        return [
            'token'      => $token,
            'region'     => $region,
            'expires_in' => self::CLIENT_TTL_SECONDS,
        ];
    }

    private function fetchToken(string $region, string $key): ?string
    {
        $url = 'https://' . $region . '.api.cognitive.microsoft.com/sts/v1.0/issueToken';
        $ch  = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Ocp-Apim-Subscription-Key: ' . $key,
                'Content-Length: 0',
            ],
            CURLOPT_TIMEOUT        => self::REQUEST_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::REQUEST_TIMEOUT,
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code !== 200) {
            Logger::warning('azure speech token failed', [
                'http_code' => $code,
                'region'    => $region,
                'curl_err'  => $err,
            ]);
            return null;
        }

        $token = trim((string) $body);
        return $token === '' ? null : $token;
    }
}
