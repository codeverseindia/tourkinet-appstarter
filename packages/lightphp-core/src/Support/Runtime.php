<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Support;

use LogicException;
use TourKinet\LightPHP\Application;

final class Runtime
{
    private static ?Application $application = null;

    public static function setApplication(Application $application): void
    {
        self::$application = $application;
    }

    public static function application(): Application
    {
        return self::$application ?? throw new LogicException('The application has not been bootstrapped.');
    }
}
