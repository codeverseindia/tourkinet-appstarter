<?php
declare(strict_types=1);

/** @var list<array{method:string,uri:string,handler:string}> */
$GLOBALS['app_routes'] = [];

function register_route(string $method, string $uri, string $handler): void
{
    if ($uri !== '/' && !preg_match('#^/(?:[A-Za-z0-9._~-]+|\{[a-z][a-z0-9_]*\})(?:/(?:[A-Za-z0-9._~-]+|\{[a-z][a-z0-9_]*\}))*$#', $uri)) {
        throw new InvalidArgumentException('Invalid route pattern.');
    }
    $GLOBALS['app_routes'][] = ['method' => $method, 'uri' => $uri, 'handler' => $handler];
}

function get(string $uri, string $handler): void
{
    register_route('GET', $uri, $handler);
}

function post(string $uri, string $handler): void
{
    register_route('POST', $uri, $handler);
}

function load_routes(): void
{
    $GLOBALS['app_routes'] = [];
    require ROOT_PATH . '/config/routes.php';
}

/** @return list<array{method:string,uri:string,handler:string}> */
function registered_routes(): array
{
    return $GLOBALS['app_routes'];
}

function dispatch_request(): void
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $rawPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    if (strlen($rawPath) > 2048 || preg_match('/%(?![0-9A-Fa-f]{2})|[\x00-\x20\x7f]/', $rawPath)) {
        render_not_found();
        return;
    }
    $path = rawurldecode($rawPath);
    if (str_contains($path, "\0") || preg_match('#(?:^|/)\.{1,2}(?:/|$)#', $path)) {
        render_not_found();
        return;
    }
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }

    $allowedMethods = [];
    foreach (registered_routes() as $route) {
        $quoted = preg_quote($route['uri'], '#');
        $regex = preg_replace_callback(
            '/\\\\\{([a-z][a-z0-9_]*)\\\\\}/',
            static fn(array $m): string => '(?P<' . $m[1] . '>[A-Za-z0-9][A-Za-z0-9._-]{0,119})',
            $quoted
        );
        if (preg_match('#^' . $regex . '/?$#D', $path, $matches) !== 1) {
            continue;
        }
        $allowedMethods[] = $route['method'];
        if ($route['method'] !== $method) {
            continue;
        }
        $params = array_filter($matches, static fn($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
        handle_route($route['handler'], $params);
        return;
    }

    if ($allowedMethods !== []) {
        header('Allow: ' . implode(', ', array_unique($allowedMethods)));
        http_response_code(405);
        view('404', ['title' => 'Method not allowed', 'message' => 'This method is not allowed for this page.']);
        return;
    }
    render_not_found();
}

function render_not_found(): void
{
    http_response_code(404);
    view('404', ['title' => 'Page not found']);
}

function handle_route(string $handler, array $params = []): void
{
    $website = config('website');
    $base = ['website' => $website];

    switch ($handler) {
        case 'home':
            $packages = getFeaturedPackages();
            view('home', $base + ['packages' => $packages]);
            return;
        case 'about':
        case 'destinations':
        case 'privacy':
            view($handler, $base);
            return;
        case 'packages':
            view('packages', $base + ['packages' => getPackages()]);
            return;
        case 'package-detail':
            $package = getPackage($params['slug']);
            if ($package === [] || !is_published($package)) {
                render_not_found();
                return;
            }
            view('package-detail', $base + ['package' => $package]);
            return;
        case 'blogs':
            view('blogs', $base + ['blogs' => getBlogs()]);
            return;
        case 'blog-detail':
            $blog = getBlog($params['slug']);
            if ($blog === [] || !is_published($blog)) {
                render_not_found();
                return;
            }
            view('blog-detail', $base + ['blog' => $blog]);
            return;
        case 'contact':
            view('contact', $base + ['csrf' => csrf_token(), 'errors' => [], 'old' => []]);
            return;
        case 'submit-inquiry':
            submit_contact_form($base);
            return;
        case 'sitemap':
            render_sitemap();
            return;
        case 'robots':
            header('Content-Type: text/plain; charset=UTF-8');
            echo "User-agent: *\nAllow: /\nSitemap: " . rtrim((string) config('app.url'), '/') . "/sitemap.xml\n";
            return;
        case 'theme':
            header('Content-Type: text/css; charset=UTF-8');
            $primary = safe_css_color($website['theme']['primary'] ?? '');
            $accent = safe_css_color($website['theme']['accent'] ?? '');
            echo ":root{--color-primary:{$primary};--color-accent:{$accent}}\n";
            return;
    }

    throw new RuntimeException('Unknown route handler.');
}

function submit_contact_form(array $base): void
{
    $data = [];
    foreach (['name', 'email', 'phone', 'message', 'website'] as $field) {
        $data[$field] = post_string($field);
    }
    $errors = [];
    if (!csrf_verify(post_string('_csrf'))) {
        $errors['_csrf'] = 'Your form session expired. Please try again.';
    }
    if ($data['website'] !== '') {
        $errors['form'] = 'Unable to submit your inquiry.';
    }
    if (!rate_limit_allow('contact', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 600)) {
        $errors['form'] = 'Please wait before submitting another inquiry.';
    }
    $errors += validate_fields($data, [
        'name' => ['required' => true, 'min' => 2, 'max' => 100],
        'email' => ['required' => true, 'email' => true, 'max' => 254],
        'phone' => ['required' => true, 'phone' => true, 'max' => 30],
        'message' => ['required' => true, 'min' => 10, 'max' => 5000],
    ]);

    $turnstileToken = post_string('cf-turnstile-response');
    $turnstileSiteKey = (string) env('TURNSTILE_SITE_KEY', '');
    $turnstileSecretKey = (string) env('TURNSTILE_SECRET_KEY', '');
    if (($turnstileSiteKey !== '' || $turnstileSecretKey !== '')
        && ($turnstileSiteKey === '' || $turnstileSecretKey === '' || !verify_turnstile($turnstileToken))) {
        $errors['form'] = 'Please complete the human verification challenge.';
    }

    if ($errors !== []) {
        http_response_code(422);
        view('contact', $base + [
            'csrf' => csrf_token(),
            'errors' => $errors,
            'old' => $data,
        ]);
        return;
    }

    $result = submitInquiry(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'message'])));
    if (($result['success'] ?? false) !== true) {
        http_response_code(503);
        view('contact', $base + [
            'csrf' => csrf_token(),
            'errors' => ['form' => 'We could not send your inquiry right now. Please try again later.'],
            'old' => $data,
        ]);
        return;
    }

    header('Location: /contact?sent=1', true, 303);
}

function render_sitemap(): void
{
    header('Content-Type: application/xml; charset=UTF-8');
    $root = rtrim((string) config('app.url'), '/');
    $urls = ['/', '/about', '/destinations', '/packages', '/blogs', '/contact', '/privacy'];
    foreach (api_items(getPackages()) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = valid_slug(text_value($item['slug'] ?? ''));
        if ($slug !== null && is_published($item)) {
            $urls[] = '/packages/' . rawurlencode($slug);
        }
    }
    foreach (api_items(getBlogs()) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = valid_slug(text_value($item['slug'] ?? ''));
        if ($slug !== null && is_published($item)) {
            $urls[] = '/blogs/' . rawurlencode($slug);
        }
    }
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach (array_unique($urls) as $url) {
        echo '<url><loc>' . e($root . $url) . "</loc></url>\n";
    }
    echo "</urlset>\n";
}

function is_published(array $item): bool
{
    $status = $item['status'] ?? '';
    return ($item['published'] ?? false) === true
        || ($item['is_published'] ?? false) === true
        || (is_string($status) && in_array(strtolower($status), ['published', 'active'], true));
}

function safe_css_color(mixed $value): string
{
    return is_string($value) && preg_match('/^#[A-Fa-f0-9]{6}$/D', $value) === 1 ? $value : '#0c6b63';
}
