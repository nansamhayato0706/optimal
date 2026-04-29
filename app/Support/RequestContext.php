<?php

declare(strict_types=1);

namespace App\Support;

final class RequestContext
{
    private $method;
    private $path;
    private $query;
    private $post;
    private $files;
    private $server;

    private function __construct(
        string $method,
        string $path,
        array $query,
        array $post,
        array $files,
        array $server
    ) {
        $this->method = $method;
        $this->path   = $path;
        $this->query  = $query;
        $this->post   = $post;
        $this->files  = $files;
        $this->server = $server;
    }

    public static function fromGlobals(): self
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $scriptDir  = rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');

        // サブディレクトリ設置時にベースパスを除去して相対パスに変換
        $path = $requestUri;
        if ($scriptDir !== '' && strncmp($path, $scriptDir, strlen($scriptDir)) === 0) {
            $path = substr($path, strlen($scriptDir));
        }
        $path = '/' . ltrim(strtok($path, '?'), '/');

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER
        );
    }

    public function method(): string { return $this->method; }
    public function path(): string   { return $this->path; }
    public function isPost(): bool   { return $this->method === 'POST'; }

    /** @return mixed */
    public function query(string $key, $default = null) {
        return $this->query[$key] ?? $default;
    }

    /** @return mixed */
    public function post(string $key, $default = null) {
        return $this->post[$key] ?? $default;
    }

    /** @return mixed */
    public function file(string $key) {
        return $this->files[$key] ?? null;
    }

    public function allPost(): array
    {
        return $this->post;
    }

    public function allFiles(): array
    {
        return $this->files;
    }

    public function header(string $name, string $default = ''): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return trim((string) ($this->server[$key] ?? $default));
    }

    public function csrfToken(): string
    {
        return (string) ($this->post['_token'] ?? '');
    }
}
