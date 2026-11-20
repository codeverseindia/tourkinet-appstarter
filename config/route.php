<?php
declare(strict_types=1);

use TourKinet\LightPHP\Application;
use TourKinet\LightPHP\Core\Response;
use TourKinet\LightPHP\Core\Router;

return static function (Router $routes, Application $application): void {
    $routes->get('/robots.txt', static function () use ($application): Response {
        $url = rtrim((string) $application->config()->get('app.url', ''), '/');
        return Response::text("User-agent: *\nAllow: /\nSitemap: {$url}/sitemap.xml\n");
    }, 'robots');

    $routes->get('/theme.css', static function () use ($application): Response {
        $theme = $application->config()->get('website.theme', []);
        $primary = is_array($theme) && is_string($theme['primary'] ?? null)
            && preg_match('/^#[A-Fa-f0-9]{6}$/D', $theme['primary']) === 1 ? $theme['primary'] : '#0c6b63';
        $accent = is_array($theme) && is_string($theme['accent'] ?? null)
            && preg_match('/^#[A-Fa-f0-9]{6}$/D', $theme['accent']) === 1 ? $theme['accent'] : '#f2a94a';
        return Response::text(":root{--color-primary:{$primary};--color-accent:{$accent}}\n")
            ->withHeader('Content-Type', 'text/css; charset=UTF-8');
    }, 'theme');
};
