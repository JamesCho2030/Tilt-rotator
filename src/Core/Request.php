<?php
namespace App\Core;

/**
 * HTTP Request abstraction.
 */
class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $headers;
    private array $files;
    private array $body;
    private array $attributes = [];

    public function __construct()
    {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->server = $_SERVER ?? [];
        $this->headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $this->files = $_FILES ?? [];
        $raw = file_get_contents('php://input');
        $this->body = $raw ? (json_decode($raw, true) ?? []) : [];
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return strtok($uri, '?') ?: '/';
    }

    public function input(string $key, $default = null)
    {
        if (isset($this->body[$key])) {
            return $this->body[$key];
        }
        if (isset($this->post[$key])) {
            return $this->post[$key];
        }
        if (isset($this->get[$key])) {
            return $this->get[$key];
        }
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
        return $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->body, $this->attributes);
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function bearerToken(): ?string
    {
        $authorization = $this->headers['Authorization'] ?? ($this->headers['authorization'] ?? null);
        if ($authorization && preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? 'unknown';
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }
}
