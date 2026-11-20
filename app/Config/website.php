<?php
declare(strict_types=1);

return [
    'company_name' => (string) env('WEBSITE_NAME', env('APP_NAME', 'Travel Website')),
    'url' => (string) env('APP_URL', 'http://127.0.0.1:8080'),
    'logo' => '/assets/images/logo.svg',
    'email' => (string) env('WEBSITE_EMAIL', 'hello@example.com'),
    'phone' => (string) env('WEBSITE_PHONE', ''),
    'meta_title' => 'Welcome to our travel website',
    'meta_description' => 'Explore travel experiences and destinations.',
    'theme' => [
        'primary' => '#0d6efd',
        'accent' => '#6c757d',
    ],
    'destinations' => [],
];
