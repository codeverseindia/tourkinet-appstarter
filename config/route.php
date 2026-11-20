<?php
declare(strict_types=1);

use CodeverseIndia\LightPHP\Application;
use CodeverseIndia\LightPHP\Core\Response;
use CodeverseIndia\LightPHP\Core\Router;

return static function (Router $routes, Application $application): void {
    $routes->get('/theme.css', static function () use ($application): Response {
        $theme = $application->config()->get('website.theme', []);
        $primary = is_array($theme) && is_string($theme['primary'] ?? null)
            && preg_match('/^#[A-Fa-f0-9]{6}$/D', $theme['primary']) === 1 ? $theme['primary'] : '#0d6efd';
        $accent = is_array($theme) && is_string($theme['accent'] ?? null)
            && preg_match('/^#[A-Fa-f0-9]{6}$/D', $theme['accent']) === 1 ? $theme['accent'] : '#6c757d';
        return Response::text(":root{--color-primary:{$primary};--color-accent:{$accent}}\n")
            ->withHeader('Content-Type', 'text/css; charset=UTF-8');
    }, 'theme');
};
