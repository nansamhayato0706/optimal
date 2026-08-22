<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Tokyo');

// 環境変数ファイルをロード
$localConfig = [];
$configSource = '';
$configCandidates = [];
$rootDir     = dirname(__DIR__);
$parentDir   = dirname($rootDir);
$grandParentDir = dirname($parentDir);

$loadEnvFile = static function (string $file): array {
    if (!is_file($file) || !is_readable($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    $values = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, 'export ') === 0) {
            $line = trim(substr($line, 7));
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($key === '') {
            continue;
        }

        $quote = substr($value, 0, 1);
        if (($quote === '"' || $quote === "'") && substr($value, -1) === $quote) {
            $value = substr($value, 1, -1);
            if ($quote === '"') {
                $value = stripcslashes($value);
            }
        }

        $values[$key] = $value;
    }

    return $values;
};

$envToConfig = static function (array $env): array {
    $map = [
        'app_url' => ['ZAITAKU_APP_URL'],
        'db_host' => ['ZAITAKU_DB_HOST'],
        'db_name' => ['ZAITAKU_DB_NAME'],
        'db_user' => ['ZAITAKU_DB_USER'],
        'db_pass' => ['ZAITAKU_DB_PASS', 'ZAITAKU_DB_PASSWORD'],
        'group_label' => ['ZAITAKU_GROUP_LABEL'],
        'max_user' => ['ZAITAKU_MAX_USER'],
        'sv_root' => ['ZAITAKU_SV_ROOT'],
        'enable_status_summary_rebuild_button' => ['ZAITAKU_STATUS_SUMMARY_REBUILD_ENABLED'],
        'inquiry_mail_enabled'  => ['ZAITAKU_INQUIRY_MAIL_ENABLED'],
        'inquiry_mail_to'       => ['ZAITAKU_INQUIRY_MAIL_TO'],
        'inquiry_mail_from'     => ['ZAITAKU_INQUIRY_MAIL_FROM'],
        'slack_webhook_url'     => ['ZAITAKU_SLACK_WEBHOOK_URL'],
        'openai_api_key'            => ['ZAITAKU_OPENAI_API_KEY'],
        'openai_transcribe_model'   => ['ZAITAKU_OPENAI_TRANSCRIBE_MODEL'],
        'transcribe_daily_seconds'  => ['ZAITAKU_TRANSCRIBE_DAILY_SECONDS'],
    ];

    $config = [];
    foreach ($map as $configKey => $envKeys) {
        foreach ($envKeys as $envKey) {
            if (array_key_exists($envKey, $env)) {
                $config[$configKey] = $env[$envKey];
                break;
            }
        }
    }

    return $config;
};

$isCli   = PHP_SAPI === 'cli';
$host    = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
$isLocal = !$isCli && in_array($host, ['localhost', '127.0.0.1', '::1'], true);

// CLI は .env.local を優先して試し、なければ本番ファイルへフォールバック
if ($isCli) {
    $envFiles = [
        $rootDir . '/.env.local',
        $rootDir . '/env.local',
        $grandParentDir . '/.env.production',
        $rootDir . '/.env.production',
    ];
} elseif ($isLocal) {
    $envFiles = [$rootDir . '/.env.local', $rootDir . '/env.local'];
} else {
    $envFiles = [$grandParentDir . '/.env.production', $rootDir . '/.env.production'];
}
$configCandidates = $envFiles;

foreach ($envFiles as $envFile) {
    $envConfig = $envToConfig($loadEnvFile($envFile));
    if ($envConfig !== []) {
        $localConfig = array_merge($localConfig, $envConfig);
        $configSource = $envFile;
        break;
    }
}

// DB
define('DB_HOST', $localConfig['db_host'] ?? 'localhost');
define('DB_NAME', $localConfig['db_name'] ?? 'jigyodan_zk_honbu');
define('DB_USER', $localConfig['db_user'] ?? 'root');
define('DB_PASS', $localConfig['db_pass'] ?? '');
define('CONFIG_SOURCE', $configSource);
define('CONFIG_CANDIDATES', implode(',', $configCandidates));

// アプリ
if (isset($localConfig['app_url']) && $localConfig['app_url'] !== '') {
    $appUrl = $localConfig['app_url'];
} elseif (PHP_SAPI !== 'cli') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $basePath = $scriptDir === '/' || $scriptDir === '.' ? '' : $scriptDir;
    $appUrl = $host === '' ? '' : $scheme . '://' . $host . $basePath;
} else {
    $appUrl = '';
}

define('APP_URL',     $appUrl);
define('GROUP_LABEL', $localConfig['group_label'] ?? '事業団');
define('STATUS_SUMMARY_REBUILD_ENABLED', !empty($localConfig['enable_status_summary_rebuild_button']));
define('INQUIRY_MAIL_ENABLED', !empty($localConfig['inquiry_mail_enabled']) && filter_var($localConfig['inquiry_mail_enabled'], FILTER_VALIDATE_BOOLEAN));
define('INQUIRY_MAIL_TO',      $localConfig['inquiry_mail_to']       ?? 'info-b@jigyodan.or.jp');
define('INQUIRY_MAIL_FROM',    $localConfig['inquiry_mail_from']     ?? 'info-b@jigyodan.or.jp');
define('SLACK_WEBHOOK_URL',    $localConfig['slack_webhook_url']     ?? '');
define('OPENAI_API_KEY',       $localConfig['openai_api_key']            ?? '');
define('OPENAI_TRANSCRIBE_MODEL', $localConfig['openai_transcribe_model']   ?? 'gpt-4o-transcribe');
define('TRANSCRIBE_DAILY_SECONDS', (int) ($localConfig['transcribe_daily_seconds'] ?? 3600));

// ファイルパス
define('UPLOAD_DIR', ($localConfig['sv_root'] ?? dirname(__DIR__) . '/') . 'img/');
define('LIB_DIR',    ($localConfig['sv_root'] ?? dirname(__DIR__) . '/') . 'lib/');
define('MAX_USER',   (int) ($localConfig['max_user'] ?? 80));
