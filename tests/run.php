<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$application = require $root . '/bootstrap/app.php';

use CodeverseIndia\LightPHP\Core\Request;

$tests = 0;
$failures = [];
$assert = static function (bool $condition, string $name) use (&$tests, &$failures): void {
    $tests++;
    if (!$condition) {
        $failures[] = $name;
    }
};

$home = $application->handle(new Request('GET', '/'));
$appName = (string) $application->config()->get('app.name', 'LightPHP App Starter');
$assert($home->status() === 200 && str_contains($home->body(), 'Welcome to ' . e($appName)), 'Welcome page renders the configured application name');

$escaped = $application->view()->render('pages/welcome', ['appName' => '<script>alert(1)</script>']);
$assert(!str_contains($escaped, '<script>alert(1)</script>')
    && str_contains($escaped, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Welcome page escapes dynamic application names');

$missing = $application->handle(new Request('GET', '/packages'));
$assert($missing->status() === 404, 'Travel App routes are not registered in the Core starter');

$routes = $application->router()->all();
$assert(count($routes) === 1 && $routes[0]['uri'] === '/', 'Starter registers only the welcome route');

echo "{$tests} starter tests, " . count($failures) . " failures\n";
foreach ($failures as $failure) {
    fwrite(STDERR, "FAIL: {$failure}\n");
}
exit($failures === [] ? 0 : 1);
