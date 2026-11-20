<?php
declare(strict_types=1);

return [
    'company_name' => (string) env('WEBSITE_NAME', env('APP_NAME', 'Travel Website')),
    'url' => (string) env('APP_URL', 'http://127.0.0.1:8080'),
    'logo' => '/assets/images/logo.svg',
    'email' => (string) env('WEBSITE_EMAIL', 'hello@example.com'),
    'phone' => (string) env('WEBSITE_PHONE', ''),
    'whatsapp' => '',
    'social' => [
        'instagram' => '',
        'facebook' => '',
    ],
    'theme' => ['primary' => '#0c6b63', 'accent' => '#f2a94a'],
    'meta_title' => 'Discover your next journey',
    'meta_description' => 'Thoughtfully planned tours and travel experiences.',
    'destinations' => [
        [
            'name' => 'Himalayan Highlands',
            'filter' => 'Himalayan Highlands',
            'summary' => 'Mountain landscapes, welcoming hill towns, and unhurried days outdoors.',
            'image' => '/assets/images/destinations/highlands.svg',
        ],
        [
            'name' => 'Kerala Coast',
            'filter' => 'Kerala Coast',
            'summary' => 'Backwaters, coastal villages, and the green landscapes of southern India.',
            'image' => '/assets/images/destinations/kerala.svg',
        ],
        [
            'name' => 'Rajasthan Heritage',
            'filter' => 'Rajasthan Heritage',
            'summary' => 'Historic forts, lively markets, and architecture shaped by desert trade routes.',
            'image' => '/assets/images/destinations/rajasthan.svg',
        ],
    ],
];
