<?php
declare(strict_types=1);

use TourKinet\LightPHP\Core\Config;
use TourKinet\LightPHP\Security\Csrf;
use TourKinet\LightPHP\Security\Validator;
use TourKinet\LightPHP\Support\Helpers;
use TourKinet\LightPHP\Support\Runtime;

if (!function_exists('view')) {
    function view(string $template, array $data = []): void
    {
        echo Runtime::application()->view()->renderPage($template, $data);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Config::environment($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Runtime::application()->config()->get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Helpers::escape($value);
    }
}

if (!function_exists('text_value')) {
    function text_value(mixed $value): string
    {
        return Helpers::text($value);
    }
}

if (!function_exists('is_https')) {
    function is_https(): bool
    {
        return isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(string $token): bool
    {
        return Csrf::verify($token);
    }
}

if (!function_exists('validate_fields')) {
    function validate_fields(array $data, array $rules): array
    {
        return Validator::fields($data, $rules);
    }
}

if (!function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        return Helpers::nonce();
    }
}

if (!function_exists('render_json_ld')) {
    function render_json_ld(array $data): void
    {
        echo Helpers::jsonLd($data);
    }
}

if (!function_exists('valid_slug')) {
    function valid_slug(string $slug): ?string
    {
        return Validator::slug($slug);
    }
}

if (!function_exists('post_string')) {
    function post_string(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }
}

