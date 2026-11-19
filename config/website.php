<?php
declare(strict_types=1);

return [
    'company_name' => (string) env('WEBSITE_NAME', env('APP_NAME', 'Travel Website')),
    'url' => (string) env('WEBSITE_URL', env('APP_URL', 'http://127.0.0.1:8080')),
    'logo' => (string) env('WEBSITE_LOGO', '/assets/images/logo.svg'),
    'email' => (string) env('WEBSITE_EMAIL', 'hello@example.com'),
    'phone' => (string) env('WEBSITE_PHONE', ''),
    'whatsapp' => (string) env('WEBSITE_WHATSAPP', ''),
    'social' => [
        'instagram' => (string) env('SOCIAL_INSTAGRAM', ''),
        'facebook' => (string) env('SOCIAL_FACEBOOK', ''),
    ],
    'theme' => ['primary' => '#0c6b63', 'accent' => '#f2a94a'],
    'meta_title' => (string) env('META_TITLE', 'Discover your next journey'),
    'meta_description' => (string) env('META_DESCRIPTION', 'Thoughtfully planned tours and travel experiences.'),
];
