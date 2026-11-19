<?php
declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'Travel Website'),
    'env' => (string) env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => rtrim((string) env('APP_URL', 'http://127.0.0.1:8080'), '/'),
    'timezone' => (string) env('APP_TIMEZONE', 'Asia/Kolkata'),
];
