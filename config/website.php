<?php
declare(strict_types=1);

return [
    'company_name' => (string) env('APP_NAME', 'Travel Website'),
    'url' => (string) env('APP_URL', 'http://127.0.0.1:8080'),
    'logo' => '/assets/images/logo.svg',
    'email' => 'hello@example.com',
    'phone' => '',
    'whatsapp' => '',
    'social' => [
        'instagram' => '',
        'facebook' => '',
    ],
    'theme' => ['primary' => '#0c6b63', 'accent' => '#f2a94a'],
    'meta_title' => 'Discover your next journey',
    'meta_description' => 'Thoughtfully planned tours and travel experiences.',
];
