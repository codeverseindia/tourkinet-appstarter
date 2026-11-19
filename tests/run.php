<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$tests = 0;
$failures = [];
$assert = static function (bool $condition, string $name) use (&$tests, &$failures): void {
    $tests++;
    if (!$condition) {
        $failures[] = $name;
    }
};

$assert(e('<script>"&') === '&lt;script&gt;&quot;&amp;', 'HTML escaping');
$assert(e(['unexpected']) === '', 'Non-scalar output is safely blank');
$assert(config('app.timezone') === 'Asia/Kolkata', 'Application configuration loaded');
$assert(valid_slug('hill-station.1') === 'hill-station.1', 'Valid route slug');
$assert(valid_slug('../secret') === null && valid_slug('bad/slug') === null, 'Traversal and slash slug rejection');
$assert(TOURKINET_ENDPOINTS === [
    'packages' => '/packages',
    'package' => '/packages/{slug}',
    'blogs' => '/blogs',
    'blog' => '/blogs/{slug}',
    'inquiry' => '/inquiries',
], 'Tour Kinet documented API paths');
$packageResponse = ['success' => true, 'data' => ['package' => ['slug' => 'sample-trip', 'package_hash' => 'pkg_123']]];
$assert(api_single_item($packageResponse, 'sample-trip') === $packageResponse['data'], 'Package detail response adapter');
$assert(api_single_item($packageResponse, 'pkg_123') === $packageResponse['data'], 'Package hash detail lookup');
$assert(valid_date('2028-02-29') && !valid_date('2027-02-29') && !valid_date('2028-2-9'), 'Inquiry date validation');
$documentedPackage = [
    'package_hash' => 'pkg_sample',
    'package_code' => 'TOUR-SAMPLE',
    'slug' => 'sample-trip',
    'title' => 'Documented Tour',
    'overview' => 'A sample itinerary.',
    'duration' => ['days' => 4, 'nights' => 3],
    'destinations' => [['name' => 'Hill Town']],
    'thumbnail' => ['url' => 'https://images.example.test/tour.webp'],
    'theme' => ['name' => 'Nature'],
    'tour_type' => ['name' => 'Private'],
    'starting_price' => '12000.00',
    'currency' => 'INR',
    'is_featured' => true,
];
ob_start();
view('packages', [
    'website' => config('website'),
    'packages' => ['success' => true, 'data' => [$documentedPackage], 'meta' => ['current_page' => 1, 'last_page' => 1]],
    'filters' => [],
]);
$packagesHtml = (string) ob_get_clean();
$assert(str_contains($packagesHtml, 'Documented Tour') && str_contains($packagesHtml, 'Hill Town') && str_contains($packagesHtml, 'INR 12000.00'), 'Documented package list fields render');
$detailResponse = [
    'success' => true,
    'data' => [
        'package' => $documentedPackage,
        'seo' => ['title' => 'Sample Tour SEO', 'description' => 'An SEO summary.', 'keywords' => ['sample tour', 'Hill Town']],
        'images' => [
            'banner' => ['url' => 'http://app.tourkinet.loc/assets/tour-banner.webp', 'caption' => 'Mountain view'],
            'thumbnail' => ['url' => 'http://app.tourkinet.loc/assets/tour-thumb.webp', 'caption' => 'Tour thumbnail'],
            'gallery' => [['url' => 'http://app.tourkinet.loc/assets/tour-gallery.webp', 'caption' => 'Local village']],
        ],
        'itinerary' => [[
            'day' => 1,
            'title' => 'Arrive',
            'description' => 'Settle in.',
            'destinations' => [['name' => 'Hill Town']],
            'images' => [['url' => 'http://app.tourkinet.loc/assets/day-one.webp', 'caption' => 'Day one view']],
        ]],
        'inclusions' => ['Breakfast'],
        'exclusions' => ['Airfare'],
        'terms_conditions' => [],
    ],
];
ob_start();
view('package-detail', ['website' => config('website'), 'package' => api_single_item($detailResponse, 'sample-trip')]);
$packageDetailHtml = (string) ob_get_clean();
$assert(str_contains($packageDetailHtml, 'Documented Tour') && str_contains($packageDetailHtml, 'Settle in.') && str_contains($packageDetailHtml, 'package=sample-trip'), 'Documented package detail fields render');
$assert(str_contains($packageDetailHtml, 'name="keywords" content="sample tour, Hill Town"')
    && str_contains($packageDetailHtml, 'property="og:image"')
    && str_contains($packageDetailHtml, 'Mountain view')
    && str_contains($packageDetailHtml, 'Local village')
    && str_contains($packageDetailHtml, 'Day one view')
    && str_contains($packageDetailHtml, 'Theme: Nature')
    && str_contains($packageDetailHtml, 'Tour type: Private'), 'Package SEO and media fields render');
$assert(safe_image_url('javascript:alert(1)') === null && safe_image_url('https://images.example.test/photo.jpg') !== null, 'Image URL scheme validation');
$assert(safe_image_url('http://app.tourkinet.loc/assets/local.webp') !== null
    && safe_image_url('http://api.tourkinet.loc/assets/local.webp') === null
    && safe_image_url('http://app.tourkinet.loc/redirect') === null, 'Local API images are restricted to app media paths');
$assert(safe_canonical_url('https://travel.example.test/package') !== null
    && safe_canonical_url('javascript:alert(1)') === null, 'SEO canonical URL validation');

$blogFixture = [
    'title' => 'Sample Story',
    'slug' => 'sample-story',
    'short_intro' => 'A sample introduction.',
    'content' => '<p>Story body.</p>',
    'published_at' => '2026-09-01T10:00:00Z',
    'category' => ['name' => 'Travel'],
    'author' => ['name' => 'Author Name', 'email' => 'author@example.test', 'bio' => 'Travel writer.'],
    'tags' => [['name' => 'Hiking']],
    'images' => [
        'banner' => ['url' => 'http://app.tourkinet.loc/assets/story-banner.webp'],
        'thumbnail' => ['url' => 'http://app.tourkinet.loc/assets/story-thumb.webp'],
    ],
    'seo' => [
        'title' => 'Story SEO Title',
        'description' => 'Story SEO description.',
        'keywords' => ['travel story'],
        'canonical_url' => 'https://travel.example.test/stories/sample-story',
        'og_image_url' => 'http://app.tourkinet.loc/assets/story-social.webp',
    ],
];
ob_start();
view('blog-detail', ['website' => config('website'), 'blog' => $blogFixture]);
$blogDetailHtml = (string) ob_get_clean();
$assert(str_contains($blogDetailHtml, 'name="keywords" content="travel story"')
    && str_contains($blogDetailHtml, 'https://travel.example.test/stories/sample-story')
    && str_contains($blogDetailHtml, 'Story SEO Title')
    && str_contains($blogDetailHtml, 'Story body.')
    && str_contains($blogDetailHtml, 'Travel writer.')
    && str_contains($blogDetailHtml, 'story-social.webp'), 'Documented blog SEO and content render');
$assert(validate_fields(['email' => 'bad'], ['email' => ['required' => true, 'email' => true]]) !== [], 'Email validation');
$assert(validate_fields(['phone' => '+91 98765-43210'], ['phone' => ['required' => true, 'phone' => true]]) === [], 'Phone validation');
$assert(validate_fields(['name' => 'A'], ['name' => ['required' => true, 'min' => 2]]) !== [], 'Minimum string length');

$_SESSION = ['_csrf' => str_repeat('a', 64)];
$assert(csrf_verify(str_repeat('a', 64)), 'CSRF accepts matching token');
$assert(!csrf_verify(str_repeat('b', 64)), 'CSRF rejects invalid token');

$assert(count(registered_routes()) >= 10, 'Routes loaded from configuration');
$testRoutes = new RouteCollection();
$testRoutes->get('/_test/{slug}', 'about');
$GLOBALS['app_routes'] = [...registered_routes(), ...$testRoutes->all()];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/_test/trip-1';
http_response_code(200);
ob_start();
dispatch_request();
$dynamicRoute = ob_get_clean();
$assert(http_response_code() === 200 && str_contains((string) $dynamicRoute, 'Our story'), 'Dynamic route parameter matching');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/not-a-route';
ob_start();
dispatch_request();
$notFound = ob_get_clean();
$assert(http_response_code() === 404 && str_contains((string) $notFound, 'Page not found'), 'Unknown route returns 404');

$key = 'test-' . bin2hex(random_bytes(8));
$assert(cache_set($key, ['value' => 'cached'], 30), 'Cache write');
$assert(cache_get($key) === ['value' => 'cached'], 'Cache read');
$assert(cache_delete($key), 'Cache delete');
$assert(cache_get($key) === null, 'Deleted cache is absent');
$expiryKey = 'expiry-' . bin2hex(random_bytes(8));
$assert(cache_set($expiryKey, ['value' => 'temporary'], 1), 'Expiring cache write');
sleep(2);
$assert(cache_get($expiryKey) === null, 'Cache expiry');
$unrelatedFile = cache_directory() . '/keep-me.txt';
file_put_contents($unrelatedFile, 'preserve');
$assert(cache_clear() && is_file($unrelatedFile), 'Cache clear preserves unrelated files');
unlink($unrelatedFile);

$assert(getPackage('../.env') === [], 'API slug traversal rejected');
$localApiUrl = parse_url('http://api.tourkinet.loc/api/v1');
$assert(is_array($localApiUrl) && api_is_local_development_url($localApiUrl), 'Local development API URL is allowed');
$assert(!api_is_local_development_url(parse_url('http://app.tourkinet.loc/api/v1') ?: []), 'Unregistered local API host is rejected');
$assert(!api_is_local_development_url(parse_url('http://api.tourkinet.loc:8081/api/v1') ?: []), 'Nonstandard local API port is rejected');
$appEnvironment = $_ENV['APP_ENV'] ?? null;
$_ENV['APP_ENV'] = 'production';
$assert(!api_is_local_development_url($localApiUrl ?: []), 'Local HTTP API is rejected outside development');
if ($appEnvironment === null) {
    unset($_ENV['APP_ENV']);
} else {
    $_ENV['APP_ENV'] = $appEnvironment;
}
$assert(api_public_ip('127.0.0.1') === null && api_public_ip('::ffff:127.0.0.1') === null && api_public_ip('8.8.8.8') === '8.8.8.8', 'API blocks private hosts');
$assert(is_published(['status' => 'published']) && !is_published(['title' => 'Unpublished?']), 'Publication checks require explicit publish state');

echo "{$tests} tests, " . count($failures) . " failures\n";
foreach ($failures as $failure) {
    fwrite(STDERR, "FAIL: {$failure}\n");
}
exit($failures === [] ? 0 : 1);
