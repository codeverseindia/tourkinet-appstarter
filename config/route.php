<?php
declare(strict_types=1);

return static function (RouteCollection $routes): void {
    $routes->get('/', 'home');
    $routes->get('/about', 'about');
    $routes->get('/destinations', 'destinations');
    $routes->get('/packages', 'packages');
    $routes->get('/packages/{slug}', 'package-detail');
    $routes->get('/blogs', 'blogs');
    $routes->get('/blogs/{slug}', 'blog-detail');
    $routes->get('/contact', 'contact');
    $routes->post('/contact', 'submit-inquiry');
    $routes->get('/privacy', 'privacy');
    $routes->get('/sitemap.xml', 'sitemap');
    $routes->get('/robots.txt', 'robots');
    $routes->get('/theme.css', 'theme');
};
