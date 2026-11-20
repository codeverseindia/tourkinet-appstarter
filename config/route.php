<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use CodeverseIndia\LightPHP\Application;
use CodeverseIndia\LightPHP\Core\Router;

return static function (Router $routes, Application $application): void {
    $routes->get('/', [new HomeController($application), 'index'], 'home');
};
