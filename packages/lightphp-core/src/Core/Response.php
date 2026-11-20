<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Core;

use InvalidArgumentException;

final class Response
{
    public function __construct(
        private readonly string $body = '',
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public static function xml(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function withHeader(string $name, string $value): self
    {
        if (preg_match('/^[A-Za-z0-9-]+$/D', $name) !== 1 || preg_match('/[\r\n\x00]/', $value)) {
            throw new InvalidArgumentException('Invalid response header.');
        }
        return new self($this->body, $this->status, array_replace($this->headers, [$name => $value]));
    }

    public static function redirect(string $location, int $status = 303): self
    {
        if (!str_starts_with($location, '/') || str_starts_with($location, '//') || preg_match('/[\r\n\x00]/', $location)) {
            throw new InvalidArgumentException('Redirect location must be a safe local path.');
        }
        return new self('', $status, ['Location' => $location]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }
}
