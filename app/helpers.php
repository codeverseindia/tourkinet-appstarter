<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars(text_value($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function text_value(mixed $value): string
{
    return is_scalar($value) ? (string) $value : '';
}

function is_https(): bool
{
    return isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
}

function view(string $template, array $data = []): void
{
    static $allowed = [
        'home', 'about', 'destinations', 'packages', 'package-detail', 'blogs',
        'blog-detail', 'contact', 'privacy', '404', '500',
    ];
    if (!in_array($template, $allowed, true)) {
        throw new InvalidArgumentException('Unknown view.');
    }
    $viewPath = ROOT_PATH . '/views/' . $template . '.php';
    if (!is_file($viewPath)) {
        throw new RuntimeException('View file is missing.');
    }

    extract($data, EXTR_SKIP);
    $title ??= (string) config('website.meta_title');
    $description ??= (string) config('website.meta_description');
    $canonical ??= rtrim((string) config('app.url'), '/') . (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    ob_start();
    require $viewPath;
    $content = (string) ob_get_clean();
    require ROOT_PATH . '/views/layouts/header.php';
    echo $content;
    require ROOT_PATH . '/views/layouts/footer.php';
}

function page_title(string $title): string
{
    return $title . ' | ' . (string) config('website.company_name');
}

function api_items(array $response): array
{
    foreach (['data', 'items', 'results'] as $key) {
        if (isset($response[$key]) && is_array($response[$key])) {
            if (array_is_list($response[$key])) {
                return $response[$key];
            }
            return isset($response[$key]['slug']) ? [$response[$key]] : array_values($response[$key]);
        }
    }
    return array_is_list($response) ? $response : (isset($response['slug']) ? [$response] : []);
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function safe_image_url(mixed $value): ?string
{
    if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
        return null;
    }
    return strtolower((string) parse_url($value, PHP_URL_SCHEME)) === 'https' ? $value : null;
}

function csp_nonce(): string
{
    $GLOBALS['csp_nonce'] ??= base64_encode(random_bytes(18));
    return $GLOBALS['csp_nonce'];
}

function render_json_ld(array $data): void
{
    echo '<script type="application/ld+json" nonce="' . e(csp_nonce()) . '">'
        . json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)
        . '</script>';
}

function valid_slug(string $slug): ?string
{
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,119}$/D', $slug) === 1 && !str_contains($slug, '..')
        ? $slug
        : null;
}
