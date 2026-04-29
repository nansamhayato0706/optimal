<?php

declare(strict_types=1);

namespace App\Support;

final class RouteRegistry
{
    private $routes = [];
    /** @var callable|null */
    private $fallback = null;

    /**
     * @param string|string[] $methods
     */
    public function add($methods, string $path, callable $handler): void
    {
        foreach ((array) $methods as $method) {
            $this->routes[strtoupper($method)][$path] = $handler;
        }
    }

    public function fallback(callable $handler): void
    {
        $this->fallback = $handler;
    }

    public function dispatch(RequestContext $request): void
    {
        $method = strtoupper($request->method());
        $path   = '/' . ltrim(strtok($request->path(), '?'), '/');

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
        }

        // GET fallback for HEAD
        if ($method === 'HEAD' && isset($this->routes['GET'][$path])) {
            ($this->routes['GET'][$path])();
            return;
        }

        if ($this->fallback !== null) {
            ($this->fallback)();
            return;
        }

        http_response_code(404);
        echo 'Not Found';
    }
}
