<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var array<string, string> */
    private array $routeParameters = [];

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $server
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'POST') {
            $override = strtoupper((string) ($_POST['_method'] ?? ''));
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $path = isset($_GET['route'])
            ? (string) $_GET['route']
            : self::pathFromUri((string) ($_SERVER['REQUEST_URI'] ?? '/'));

        $body = $_POST;
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            if (is_array($decoded)) {
                $body = array_merge($body, $decoded);
            }
        }

        return new self($method, self::normalizePath($path), $_GET, $body, $_FILES, $_SERVER);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParameters[$key] ?? $default;
    }

    /** @param array<string, string> $parameters */
    public function setRouteParameters(array $parameters): void
    {
        $this->routeParameters = $parameters;
    }

    public function expectsJson(): bool
    {
        $accept = strtolower((string) ($this->server['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string) ($this->server['HTTP_X_REQUESTED_WITH'] ?? ''));
        return str_contains($accept, 'application/json')
            || $requestedWith === 'xmlhttprequest'
            || str_starts_with($this->path, '/api/');
    }

    private static function pathFromUri(string $uri): string
    {
        $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($base !== '' && $base !== '.' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
        }

        return $path;
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . trim((string) parse_url($path, PHP_URL_PATH), '/');
        return $path === '//' ? '/' : $path;
    }
}
