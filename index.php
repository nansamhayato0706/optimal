<?php

declare(strict_types=1);

try {
    require_once __DIR__ . '/bootstrap/app.php';

    app_routes()->dispatch(app_request());
} catch (\Throwable $e) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $context = [
        'time' => date('Y-m-d H:i:s'),
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'config_source' => defined('CONFIG_SOURCE') ? CONFIG_SOURCE : '',
        'config_candidates' => defined('CONFIG_CANDIDATES') ? CONFIG_CANDIDATES : '',
        'db_host' => defined('DB_HOST') ? DB_HOST : '',
        'db_name' => defined('DB_NAME') ? DB_NAME : '',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    ];

    $line = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line !== false) {
        @error_log($line . PHP_EOL, 3, $logDir . '/app.log');
        error_log('[zaitakukanri_honbu_new] ' . $line);
    }

    http_response_code(500);
    echo 'Internal Server Error';
}
