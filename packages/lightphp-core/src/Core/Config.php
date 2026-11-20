<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Core;

final class Config
{
    public function __construct(private readonly array $values)
    {
    }

    public static function loadEnvironment(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
                continue;
            }

            $key = $matches[1];
            $value = trim($matches[2]);
            if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true) && $value[0] === $value[-1]) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                $_ENV[$key] = $value;
                putenv($key . '=' . $value);
            }
        }
    }

    public static function environment(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            default => $value,
        };
    }

    public static function fromProject(string $directory, array $files = ['app']): self
    {
        $values = [];
        foreach ($files as $name) {
            if (!is_string($name) || preg_match('/^[a-z][a-z0-9_-]*$/D', $name) !== 1) {
                throw new \InvalidArgumentException('Invalid configuration file name.');
            }
            $file = rtrim($directory, DIRECTORY_SEPARATOR) . '/' . $name . '.php';
            if (is_file($file)) {
                $value = require $file;
                if (!is_array($value)) {
                    throw new \RuntimeException('Configuration files must return arrays.');
                }
                $values[$name] = $value;
            }
        }

        return new self($values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->values;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function all(): array
    {
        return $this->values;
    }
}
