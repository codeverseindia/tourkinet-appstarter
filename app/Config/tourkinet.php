<?php
declare(strict_types=1);

return [
    'api_url' => rtrim((string) env('TOURKINET_API_URL', 'https://api.tourkinet.com/api/v1'), '/'),
    'api_key' => (string) env('TOURKINET_API_KEY', env('TOURKINET_ACCESS_KEY', '')),
    'client_domain' => (string) env('TOURKINET_CLIENT_DOMAIN', parse_url((string) env('APP_URL', ''), PHP_URL_HOST) ?: ''),
    'timeout' => 8,
    'endpoints' => [
        'packages' => '/packages',
        'package' => '/packages/{slug}',
        'blogs' => '/blogs',
        'blog' => '/blogs/{slug}',
        'inquiry' => '/inquiries',
    ],
    'cache' => [
        'packages_ttl' => max(0, (int) env('CACHE_PACKAGES_TTL', env('CACHE_DEFAULT_TTL', 900))),
        'blogs_ttl' => max(0, (int) env('CACHE_BLOGS_TTL', 1800)),
    ],
    'turnstile' => [
        'site_key' => (string) env('TURNSTILE_SITE_KEY', ''),
        'secret_key' => (string) env('TURNSTILE_SECRET_KEY', ''),
    ],
];
