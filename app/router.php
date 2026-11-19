<?php
declare(strict_types=1);

/** @var list<array{method:string,uri:string,handler:string}> */
$GLOBALS['app_routes'] = [];

final class RouteCollection
{
    /** @var list<array{method:string,uri:string,handler:string}> */
    private array $routes = [];

    public function get(string $uri, string $handler): self
    {
        return $this->add('GET', $uri, $handler);
    }

    public function post(string $uri, string $handler): self
    {
        return $this->add('POST', $uri, $handler);
    }

    /** @return list<array{method:string,uri:string,handler:string}> */
    public function all(): array
    {
        return $this->routes;
    }

    private function add(string $method, string $uri, string $handler): self
    {
        if ($uri !== '/' && !preg_match('#^/(?:[A-Za-z0-9._~-]+|\{[a-z][a-z0-9_]*\})(?:/(?:[A-Za-z0-9._~-]+|\{[a-z][a-z0-9_]*\}))*$#', $uri)) {
            throw new InvalidArgumentException('Invalid route pattern.');
        }
        $this->routes[] = ['method' => $method, 'uri' => $uri, 'handler' => $handler];
        return $this;
    }
}

function load_routes(): void
{
    $routes = new RouteCollection();
    $configureRoutes = require ROOT_PATH . '/config/route.php';
    if (!$configureRoutes instanceof Closure) {
        throw new RuntimeException('Route configuration must return a closure.');
    }
    $configureRoutes($routes);
    $GLOBALS['app_routes'] = $routes->all();
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
            $filters = query_filters([
                'theme' => 120,
                'type' => 120,
                'destination' => 120,
                'is_featured' => 5,
            ]);
            foreach (['duration_min', 'duration_max', 'page', 'per_page'] as $field) {
                $value = $_GET[$field] ?? null;
                if (is_string($value) && preg_match('/^[0-9]{1,4}$/D', $value) === 1) {
                    $filters[$field] = (int) $value;
                }
            }
            if (isset($filters['per_page'])) {
                $filters['per_page'] = min(50, max(1, $filters['per_page']));
            }
            foreach (['duration_min', 'duration_max', 'page'] as $field) {
                if (isset($filters[$field]) && $filters[$field] < 1) {
                    unset($filters[$field]);
                }
            }
            if (isset($filters['is_featured']) && !in_array($filters['is_featured'], ['true', 'false', '1', '0'], true)) {
                unset($filters['is_featured']);
            }
            $packages = getPackages($filters);
            view('packages', $base + ['packages' => $packages, 'filters' => $filters]);
            return;
        case 'package-detail':
            $package = getPackage($params['slug']);
            if ($package === []) {
                render_not_found();
                return;
            }
            view('package-detail', $base + ['package' => $package]);
            return;
        case 'blogs':
            $filters = query_filters(['search' => 120, 'category' => 120, 'tag' => 120]);
            foreach (['page', 'per_page'] as $field) {
                $value = $_GET[$field] ?? null;
                if (is_string($value) && preg_match('/^[0-9]{1,4}$/D', $value) === 1) {
                    $filters[$field] = (int) $value;
                }
            }
            if (isset($filters['per_page'])) {
                $filters['per_page'] = min(50, max(1, $filters['per_page']));
            }
            if (isset($filters['page']) && $filters['page'] < 1) {
                unset($filters['page']);
            }
            $blogs = getBlogs($filters);
            view('blogs', $base + ['blogs' => $blogs, 'filters' => $filters]);
            return;
        case 'blog-detail':
            $blog = getBlog($params['slug']);
            if ($blog === []) {
                render_not_found();
                return;
            }
            view('blog-detail', $base + ['blog' => $blog]);
            return;
        case 'contact':
            $packageSlug = $_GET['package'] ?? '';
            $packageSlug = is_string($packageSlug) ? (valid_slug($packageSlug) ?? '') : '';
            $packageResponse = $packageSlug === '' ? [] : getPackage($packageSlug);
            $package = $packageResponse['package'] ?? $packageResponse;
            if (!is_array($package)) {
                $package = [];
            }
            view('contact', $base + [
                'csrf' => csrf_token(),
                'errors' => [],
                'old' => [],
                'package' => $package,
                'attribution' => inquiry_attribution(),
            ]);
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
    foreach ([
        'inquiry_type', 'first_name', 'last_name', 'email', 'phone', 'destination',
        'travel_start_date', 'travel_end_date', 'adults', 'children', 'infants',
        'estimated_budget', 'message', 'package_hash', 'package_code', 'package_name',
        'utm_source', 'utm_medium', 'utm_campaign', 'website',
    ] as $field) {
        $data[$field] = post_string($field);
    }
    $data['inquiry_type'] = $data['inquiry_type'] !== '' ? $data['inquiry_type'] : 'regular';
    $data['adults'] = $data['adults'] !== '' ? $data['adults'] : '1';
    $data['children'] = $data['children'] !== '' ? $data['children'] : '0';
    $data['infants'] = $data['infants'] !== '' ? $data['infants'] : '0';
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
        'inquiry_type' => ['allowed' => ['regular', 'package']],
        'first_name' => ['max' => 100],
        'last_name' => ['max' => 100],
        'email' => ['email' => true, 'max' => 254],
        'phone' => ['phone' => true, 'max' => 30],
        'destination' => ['max' => 120],
        'travel_start_date' => ['max' => 10],
        'travel_end_date' => ['max' => 10],
        'adults' => ['max' => 2],
        'children' => ['max' => 2],
        'infants' => ['max' => 2],
        'estimated_budget' => ['max' => 24],
        'message' => ['max' => 5000],
        'package_hash' => ['max' => 255],
        'package_code' => ['max' => 120],
        'package_name' => ['max' => 200],
        'utm_source' => ['max' => 200],
        'utm_medium' => ['max' => 200],
        'utm_campaign' => ['max' => 200],
    ]);
    if ($data['email'] === '' && $data['phone'] === '') {
        $errors['contact_method'] = 'Enter an email address or phone number so we can reach you.';
    }
    foreach (['adults' => [1, 50], 'children' => [0, 50], 'infants' => [0, 50]] as $field => [$minimum, $maximum]) {
        if (filter_var($data[$field], FILTER_VALIDATE_INT) === false
            || (int) $data[$field] < $minimum
            || (int) $data[$field] > $maximum) {
            $errors[$field] = 'Enter a whole number between ' . $minimum . ' and ' . $maximum . '.';
        }
    }
    if ($data['estimated_budget'] !== '' && (!is_numeric($data['estimated_budget']) || !is_finite((float) $data['estimated_budget']) || (float) $data['estimated_budget'] < 0)) {
        $errors['estimated_budget'] = 'Enter a valid non-negative budget.';
    }
    foreach (['travel_start_date', 'travel_end_date'] as $field) {
        if ($data[$field] !== '' && !valid_date($data[$field])) {
            $errors[$field] = 'Enter a valid date.';
        }
    }
    if ($data['travel_start_date'] !== '' && $data['travel_end_date'] !== ''
        && valid_date($data['travel_start_date']) && valid_date($data['travel_end_date'])
        && $data['travel_end_date'] < $data['travel_start_date']) {
        $errors['travel_end_date'] = 'The end date must be on or after the start date.';
    }
    if ($data['inquiry_type'] === 'package'
        && $data['package_hash'] === '' && $data['package_code'] === '' && $data['package_name'] === '') {
        $errors['package_name'] = 'Enter the package name for a package inquiry.';
    }

    $turnstileToken = post_string('cf-turnstile-response');
    $turnstileSiteKey = (string) env('TURNSTILE_SITE_KEY', '');
    $turnstileSecretKey = (string) env('TURNSTILE_SECRET_KEY', '');
    if (($turnstileSiteKey !== '' || $turnstileSecretKey !== '')
        && ($turnstileSiteKey === '' || $turnstileSecretKey === '' || !verify_turnstile($turnstileToken))) {
        $errors['form'] = 'Please complete the human verification challenge.';
    }

    if ($errors !== []) {
        http_response_code(422);
        $postedPackage = $data['inquiry_type'] === 'package' ? [
            'package_hash' => $data['package_hash'],
            'package_code' => $data['package_code'],
            'name' => $data['package_name'],
        ] : [];
        view('contact', $base + [
            'csrf' => csrf_token(),
            'errors' => $errors,
            'old' => $data,
            'package' => $postedPackage,
            'attribution' => $data,
        ]);
        return;
    }

    $payload = array_intersect_key($data, array_flip([
        'inquiry_type', 'first_name', 'last_name', 'email', 'phone', 'destination',
        'travel_start_date', 'travel_end_date', 'adults', 'children', 'infants',
        'estimated_budget', 'message', 'package_hash', 'package_code', 'package_name',
        'utm_source', 'utm_medium', 'utm_campaign',
    ]));
    $payload['landing_page'] = rtrim((string) config('app.url'), '/')
        . (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/contact'), PHP_URL_PATH) ?: '/contact');
    foreach (['adults', 'children', 'infants'] as $field) {
        $payload[$field] = (int) $payload[$field];
    }
    if ($payload['estimated_budget'] === '') {
        unset($payload['estimated_budget']);
    } else {
        $payload['estimated_budget'] = (float) $payload['estimated_budget'];
    }
    foreach ([
        'travel_start_date', 'travel_end_date', 'destination', 'email', 'phone',
        'first_name', 'last_name', 'message', 'package_hash', 'package_code',
        'package_name', 'utm_source', 'utm_medium', 'utm_campaign',
    ] as $field) {
        if ($payload[$field] === '') {
            unset($payload[$field]);
        }
    }
    if ($payload['inquiry_type'] !== 'package') {
        unset($payload['package_hash'], $payload['package_code'], $payload['package_name']);
    }
    $result = submitInquiry($payload);
    if (($result['success'] ?? false) !== true) {
        http_response_code(503);
        $postedPackage = $data['inquiry_type'] === 'package' ? [
            'package_hash' => $data['package_hash'],
            'package_code' => $data['package_code'],
            'name' => $data['package_name'],
        ] : [];
        view('contact', $base + [
            'csrf' => csrf_token(),
            'errors' => ['form' => 'We could not send your inquiry right now. Please try again later.'],
            'old' => $data,
            'package' => $postedPackage,
            'attribution' => $data,
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
        if ($slug !== null) {
            $urls[] = '/packages/' . rawurlencode($slug);
        }
    }
    foreach (api_items(getBlogs()) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = valid_slug(text_value($item['slug'] ?? ''));
        if ($slug !== null) {
            $urls[] = '/blogs/' . rawurlencode($slug);
        }
    }
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach (array_unique($urls) as $url) {
        echo '<url><loc>' . e($root . $url) . "</loc></url>\n";
    }
    echo "</urlset>\n";
}

function query_filters(array $limits): array
{
    $filters = [];
    foreach ($limits as $field => $maximum) {
        $value = $_GET[$field] ?? null;
        if (is_string($value) && trim($value) !== '') {
            $trimmed = trim($value);
            $filters[$field] = function_exists('mb_substr') ? mb_substr($trimmed, 0, $maximum) : substr($trimmed, 0, $maximum);
        }
    }
    return $filters;
}

function valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function inquiry_attribution(): array
{
    return query_filters(['utm_source' => 200, 'utm_medium' => 200, 'utm_campaign' => 200]);
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
