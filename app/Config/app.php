<?php
declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'LightPHP App Starter'),
    'env' => (string) env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => rtrim((string) env('APP_URL', 'http://127.0.0.1:8080'), '/'),
    'timezone' => (string) env('APP_TIMEZONE', 'UTC'),
    'storage_path' => dirname(__DIR__, 2) . '/writable',
    'session_name' => 'lightphp_session',
    'max_request_bytes' => 1048576,
    'cache' => [
        'enabled' => (bool) env('CACHE_ENABLED', true),
        'max_stale' => max(0, (int) env('CACHE_MAX_STALE', 3600)),
    ],
    'security_headers' => [
        'style-src' => ["'self'", 'https://cdn.jsdelivr.net'],
    ],
];
