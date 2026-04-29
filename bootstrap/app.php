<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('ZK_OPTIMAL_SESSID');
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relativePath = substr($class, strlen($prefix));
    $filePath = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relativePath) . '.php';
    if (is_file($filePath)) {
        require_once $filePath;
    }
});

require_once dirname(__DIR__) . '/config/config.php';

use App\Support\AppConfig;
use App\Support\Container;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\PdoFactory;
use App\Support\RequestContext;
use App\Support\RouteRegistry;
use App\Support\SessionStore;

function app_container(): Container
{
    static $container = null;
    if ($container === null) {
        $container = require __DIR__ . '/container.php';
    }
    return $container;
}

function app_config(): AppConfig
{
    return app_container()->get(AppConfig::class);
}

function app_request(): RequestContext
{
    return app_container()->get(RequestContext::class);
}

function app_session(): SessionStore
{
    return app_container()->get(SessionStore::class);
}

function app_routes(): RouteRegistry
{
    static $routes = null;
    if ($routes === null) {
        $routes = require __DIR__ . '/routes.php';
    }
    return $routes;
}

Database::initialize(new PdoFactory(app_config()));

function csrf_field(): string
{
    return Csrf::fieldHtml();
}

function csrf_verify_or_abort(RequestContext $request): void
{
    if (!Csrf::verifyRequest($request)) {
        http_response_code(419);
        header('Location: error.php');
        exit;
    }
}
