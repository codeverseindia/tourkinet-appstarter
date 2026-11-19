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
$assert(safe_image_url('javascript:alert(1)') === null && safe_image_url('https://images.example.test/photo.jpg') !== null, 'Image URL scheme validation');
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
$assert(getPackages() === [], 'Unconfigured API fails safely');
$assert(api_public_ip('127.0.0.1') === null && api_public_ip('::ffff:127.0.0.1') === null && api_public_ip('8.8.8.8') === '8.8.8.8', 'API blocks private hosts');
$assert(is_published(['status' => 'published']) && !is_published(['title' => 'Unpublished?']), 'Publication checks require explicit publish state');

echo "{$tests} tests, " . count($failures) . " failures\n";
foreach ($failures as $failure) {
    fwrite(STDERR, "FAIL: {$failure}\n");
}
exit($failures === [] ? 0 : 1);
