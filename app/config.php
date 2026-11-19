<?php
declare(strict_types=1);

function load_environment(string $file): void
{
    if (!is_file($file) || !is_readable($file)) {
        return;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
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

function env(string $key, mixed $default = null): mixed
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

function config(string $key, mixed $default = null): mixed
{
    static $config;
    $config ??= [
        'app' => require ROOT_PATH . '/config/app.php',
        'website' => require ROOT_PATH . '/config/website.php',
    ];

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}
