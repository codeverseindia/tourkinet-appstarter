<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Security;

use TourKinet\LightPHP\Core\Config;

final class SecurityHeaders
{
    public static function send(Config $config, string $nonce, bool $https): void
    {
        $policy = is_array($config->get('app.security_headers', null))
            ? $config->get('app.security_headers')
            : [];
        $directives = [
            'default-src' => ["'self'"],
            'img-src' => ["'self'", 'https:', 'data:'],
            'style-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'"],
            'frame-src' => ["'self'"],
            'connect-src' => ["'self'"],
            'form-action' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
        ];
        foreach ($policy as $name => $sources) {
            if (isset($directives[$name]) && is_array($sources)) {
                $directives[$name] = $sources;
            }
        }
        $directives['script-src'][] = "'nonce-{$nonce}'";
        $csp = [];
        foreach ($directives as $name => $sources) {
            $csp[] = $name . ' ' . implode(' ', array_unique(array_map('strval', $sources)));
        }
        header('Content-Security-Policy: ' . implode('; ', $csp));
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('X-Frame-Options: DENY');
        if ($config->get('app.env') === 'production' && $https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
