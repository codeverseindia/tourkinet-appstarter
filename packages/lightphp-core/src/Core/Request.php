<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Core;

final readonly class Request
{
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $post = [],
        public array $server = [],
        public int $contentLength = 0,
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_SERVER,
            max(0, (int) ($_SERVER['CONTENT_LENGTH'] ?? 0)),
        );
    }

    public function queryString(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public function postString(string $key): string
    {
        $value = $this->post[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    public function clientIp(): string
    {
        $address = $this->server['REMOTE_ADDR'] ?? '';
        return is_string($address) && $address !== '' ? $address : 'unknown';
    }

    public function isSecure(): bool
    {
        return isset($this->server['HTTPS']) && strtolower((string) $this->server['HTTPS']) !== 'off';
    }
}
