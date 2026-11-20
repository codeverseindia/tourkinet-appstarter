<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$application = require $root . '/bootstrap/app.php';

use TourKinet\LightPHP\Core\Request;

$tests = 0;
$failures = [];
$assert = static function (bool $condition, string $name) use (&$tests, &$failures): void {
    $tests++;
    if (!$condition) {
        $failures[] = $name;
    }
};

$about = $application->handle(new Request('GET', '/about'));
$assert($about->status() === 200 && str_contains($about->body(), 'Travel with more meaning.'), 'Client website serves static about page');
$assert(str_contains($about->body(), 'Our mission') && str_contains($about->body(), 'How we help'), 'About page includes mission and services');
$assert(str_contains($about->body(), '<meta name="description"'), 'Page metadata is rendered through the shared layout');

$destinations = $application->handle(new Request('GET', '/destinations'));
$assert($destinations->status() === 200
    && str_contains($destinations->body(), 'Himalayan Highlands')
    && str_contains($destinations->body(), 'highlands.svg'), 'Destinations page renders static configured cards');

$missing = $application->handle(new Request('GET', '/missing-page'));
$assert($missing->status() === 404 && str_contains($missing->body(), 'Page not found'), 'Unknown route uses the 404 template');

$method = $application->handle(new Request('PUT', '/contact'));
$assert($method->status() === 405, 'Unsupported route method returns 405');

$sessionDirectory = sys_get_temp_dir() . '/lightphp-session-test-' . bin2hex(random_bytes(5));
mkdir($sessionDirectory, 0700);
session_save_path($sessionDirectory);
$sessionId = 'lightphp' . bin2hex(random_bytes(6));
session_id($sessionId);
session_start();
$contact = $application->handle(new Request('GET', '/contact'));
$assert($contact->status() === 200
    && preg_match('/name="_csrf" value="[a-f0-9]{64}"/', $contact->body()) === 1
    && str_contains($contact->body(), 'name="website"'), 'Inquiry form includes CSRF and honeypot protections');
session_write_close();
$sessionFile = $sessionDirectory . '/sess_' . $sessionId;
if (is_file($sessionFile)) {
    unlink($sessionFile);
}
rmdir($sessionDirectory);

$robots = $application->handle(new Request('GET', '/robots.txt'));
$assert($robots->status() === 200 && str_contains($robots->body(), 'Sitemap:'), 'Robots route is available');
$theme = $application->handle(new Request('GET', '/theme.css'));
$assert($theme->status() === 200 && str_contains($theme->body(), '--color-primary'), 'Client theme route is available');

$routes = $application->router()->all();
$assert(count($routes) >= 16, 'Travel and client routes are registered explicitly');

echo "{$tests} website tests, " . count($failures) . " failures\n";
foreach ($failures as $failure) {
    fwrite(STDERR, "FAIL: {$failure}\n");
}
exit($failures === [] ? 0 : 1);
