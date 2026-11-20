<?php
declare(strict_types=1);

use TourKinet\LightPHP\Application;
use TourKinet\TravelApp\TravelApplication;

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    throw new RuntimeException('Composer dependencies are missing. Run composer install.');
}
require $autoload;

$application = Application::boot(
    $root,
    ['app', 'website', 'tourkinet'],
    $root . '/app/Config',
    $root . '/app/Views',
);
TravelApplication::register($application);

$registerClientRoutes = require $root . '/config/route.php';
if (!$registerClientRoutes instanceof Closure) {
    throw new RuntimeException('Route configuration must return a closure.');
}
$registerClientRoutes($application->router(), $application);

return $application;
