<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);
$autoload = is_file($root . '/vendor/autoload.php')
    ? $root . '/vendor/autoload.php'
    : dirname(__DIR__) . '/vendor/autoload.php';
require $autoload;

use TourKinet\LightPHP\Cache\FileCache;
use TourKinet\LightPHP\Core\Request;
use TourKinet\LightPHP\Core\Response;
use TourKinet\LightPHP\Core\Router;
use TourKinet\LightPHP\Core\View;
use TourKinet\LightPHP\Security\Csrf;
use TourKinet\LightPHP\Security\Validator;
use TourKinet\LightPHP\Support\Helpers;

$tests = 0;
$failures = [];
$assert = static function (bool $condition, string $name) use (&$tests, &$failures): void {
    $tests++;
    if (!$condition) {
        $failures[] = $name;
    }
};

$router = new Router();
$router->get('/items/{slug}', static fn(Request $request, array $params): Response => Response::text($params['slug']), 'item.show');
$response = $router->dispatch(new Request('GET', '/items/day-trip'));
$assert($response->status() === 200 && $response->body() === 'day-trip', 'Route parameters dispatch');
$assert($router->urlFor('item.show', ['slug' => 'day trip'], ['page' => 2]) === '/items/day%20trip?page=2', 'Named route URL generation');
$assert($router->dispatch(new Request('POST', '/items/day-trip'))->status() === 405, 'Route method mismatch returns 405');
$assert($router->dispatch(new Request('GET', '/missing'))->status() === 404, 'Unknown route returns 404');
$assert($router->dispatch(new Request('GET', '/items/%2e%2e'))->status() === 404, 'Encoded traversal is rejected');
$duplicateRejected = false;
try {
    $router->get('/items/{slug}', static fn(): Response => Response::text('duplicate'));
} catch (LogicException) {
    $duplicateRejected = true;
}
$assert($duplicateRejected, 'Duplicate routes require explicit override');
$router->get('/items/{slug}', static fn(): Response => Response::text('overridden'), 'item.show', true);
$assert($router->dispatch(new Request('GET', '/items/day-trip'))->body() === 'overridden', 'Explicit route override replaces default');

$redirectRejected = false;
try {
    Response::redirect('https://example.test');
} catch (InvalidArgumentException) {
    $redirectRejected = true;
}
$assert($redirectRejected, 'Redirects cannot target external origins');
$assert(Helpers::escape('<script>"&') === '&lt;script&gt;&quot;&amp;', 'HTML output is escaped');
$assert(Validator::fields(['email' => 'bad'], ['email' => ['required' => true, 'email' => true]]) !== [], 'Email validation rejects invalid values');
$assert(Validator::slug('../secret') === null && Validator::slug('hill-station') === 'hill-station', 'Slug validation rejects traversal');
$_SESSION = ['_csrf' => str_repeat('a', 64)];
$assert(Csrf::verify(str_repeat('a', 64)) && !Csrf::verify(str_repeat('b', 64)), 'CSRF tokens are compared safely');

$tmp = sys_get_temp_dir() . '/lightphp-core-test-' . bin2hex(random_bytes(6));
$clientViews = $tmp . '/client';
$packageViews = $tmp . '/package';
mkdir($clientViews . '/layouts', 0700, true);
mkdir($clientViews . '/pages', 0700, true);
mkdir($packageViews . '/layouts', 0700, true);
mkdir($packageViews . '/pages', 0700, true);
file_put_contents($clientViews . '/pages/home.php', 'client override');
file_put_contents($packageViews . '/pages/home.php', 'package default');
file_put_contents($packageViews . '/layouts/header.php', '<header>default</header>');
file_put_contents($packageViews . '/layouts/footer.php', '<footer>default</footer>');
$views = new View();
$views->addPath($packageViews, 100);
$views->addPath($clientViews, 0);
$rendered = $views->renderPage('pages/home');
$assert(str_contains($rendered, 'client override') && !str_contains($rendered, 'package default'), 'Client templates override package defaults');
$assert(str_contains($rendered, '<header>default</header>') && str_contains($rendered, '<footer>default</footer>'), 'Package layout defaults remain available');
unlink($clientViews . '/pages/home.php');
$assert($views->render('pages/home') === 'package default', 'Package view is used when a client override is absent');
unlink($packageViews . '/pages/home.php');
unlink($packageViews . '/layouts/header.php');
unlink($packageViews . '/layouts/footer.php');
rmdir($clientViews . '/pages');
rmdir($clientViews . '/layouts');
rmdir($packageViews . '/pages');
rmdir($packageViews . '/layouts');
rmdir($clientViews);
rmdir($packageViews);
rmdir($tmp);

$cacheRoot = sys_get_temp_dir() . '/lightphp-cache-test-' . bin2hex(random_bytes(6));
mkdir($cacheRoot, 0700);
$cache = new FileCache($cacheRoot, 30);
$assert($cache->set('sample', ['value' => 1], 30) && $cache->get('sample') === ['value' => 1], 'File cache stores and reads JSON values');
$corruptPath = $cacheRoot . '/cache/' . hash('sha256', 'corrupt') . '.json';
file_put_contents($corruptPath, '{invalid');
$assert($cache->get('corrupt') === null, 'Corrupted cache data is safely ignored');
$assert($cache->set('stale', ['value' => 2], 1), 'Cache accepts expiring entries');
sleep(2);
$assert($cache->get('stale') === null && $cache->getStale('stale') === ['value' => 2], 'Expired data is available only within stale allowance');
file_put_contents($cacheRoot . '/cache/keep-me.txt', 'preserve');
$assert($cache->clear() && is_file($cacheRoot . '/cache/keep-me.txt'), 'Cache clear preserves unrelated files');
unlink($cacheRoot . '/cache/keep-me.txt');
rmdir($cacheRoot . '/cache');
rmdir($cacheRoot);

echo "{$tests} core tests, " . count($failures) . " failures\n";
foreach ($failures as $failure) {
    fwrite(STDERR, "FAIL: {$failure}\n");
}
exit($failures === [] ? 0 : 1);
