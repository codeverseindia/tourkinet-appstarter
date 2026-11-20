<?php
declare(strict_types=1);

namespace App\Controllers;

use CodeverseIndia\LightPHP\Application;
use CodeverseIndia\LightPHP\Core\Request;
use CodeverseIndia\LightPHP\Core\Response;

final class HomeController
{
    public function __construct(private readonly Application $application)
    {
    }

    public function index(Request $request): Response
    {
        $appName = (string) $this->application->config()->get('app.name', 'LightPHP App Starter');
        return Response::html($this->application->view()->render('pages/welcome', [
            'appName' => $appName,
        ]));
    }
}
