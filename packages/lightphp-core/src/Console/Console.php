<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Console;

use Throwable;
use TourKinet\LightPHP\Application;

final class Console
{
    public static function run(array $arguments, Application $application): int
    {
        $command = (string) ($arguments[1] ?? 'help');
        $options = array_slice($arguments, 2);
        if ($command !== 'serve' && $options !== []) {
            fwrite(STDERR, "This command does not accept options.\n");
            return 2;
        }

        return match ($command) {
            'help', '--help', '-h' => self::help(),
            'version', '--version' => self::version($application),
            'routes' => self::routes($application),
            'cache:clear' => self::clearCache($application),
            'doctor' => self::doctor($application),
            'serve' => self::serve($application, $options),
            default => self::unknown($command),
        };
    }

    private static function help(): int
    {
        echo <<<HELP
LightPHP

Usage: php spark <command> [options]

Commands:
  serve [--host=IP] [--port=8080]
                      Start the development server (loopback by default)
  routes              List registered routes
  cache:clear         Clear public response cache
  version             Show installed package versions
  doctor              Check application requirements
  help                Show this help

HELP;
        return 0;
    }

    private static function version(Application $application): int
    {
        echo "LightPHP\n\n";
        echo 'Core Version:       ' . self::installedVersion('codeverseindia/lightphp-core') . "\n";
        foreach ($application->packageVersions() as $name => $version) {
            printf("%s %s\n", $name . ' Version:', $version);
        }
        echo 'PHP Version:        ' . PHP_VERSION . "\n";
        echo 'Environment:        ' . (string) $application->config()->get('app.env', 'local') . "\n";
        return 0;
    }

    private static function routes(Application $application): int
    {
        $routes = $application->router()->all();
        if ($routes === []) {
            fwrite(STDERR, "No routes are registered.\n");
            return 1;
        }
        foreach ($routes as $route) {
            printf("%-7s %-40s %s\n", $route['method'], $route['uri'], $route['name'] ?? '');
        }
        return 0;
    }

    private static function clearCache(Application $application): int
    {
        if (!$application->cache()->clear()) {
            fwrite(STDERR, "Cache could not be cleared; check private storage permissions and paths.\n");
            return 1;
        }
        echo "Cache cleared.\n";
        return 0;
    }

    private static function doctor(Application $application): int
    {
        $root = $application->root();
        $storage = (string) $application->config()->get('app.storage_path', $root . '/storage');
        $checks = [
            'PHP 8.5+' => version_compare(PHP_VERSION, '8.5.0', '>='),
            'PHP cURL extension' => extension_loaded('curl'),
            'PHP JSON extension' => extension_loaded('json'),
            'Composer autoload' => is_file($root . '/vendor/autoload.php'),
            'Core package installed' => self::packageInstalled('codeverseindia/lightphp-core'),
            'Environment loaded' => $application->config()->get('app.env') !== null,
            'Private storage writable' => is_dir($storage) && is_writable($storage),
            'Routes registered' => $application->router()->all() !== [],
        ];
        foreach ($application->doctorChecks() as $name => $check) {
            try {
                $checks[$name] = (bool) $check();
            } catch (Throwable) {
                $checks[$name] = false;
            }
        }

        $failed = false;
        foreach ($checks as $name => $passed) {
            printf("[%s] %s\n", $passed ? 'OK' : 'FAIL', $name);
            $failed = $failed || !$passed;
        }
        return $failed ? 1 : 0;
    }

    private static function serve(Application $application, array $options): int
    {
        $host = '127.0.0.1';
        $port = 8080;
        foreach ($options as $option) {
            if (preg_match('/^--port=(\d{1,5})$/D', $option, $matches) === 1) {
                $port = (int) $matches[1];
            } elseif (preg_match('/^--host=(.+)$/D', $option, $matches) === 1
                && filter_var($matches[1], FILTER_VALIDATE_IP) !== false) {
                $host = $matches[1];
            } else {
                fwrite(STDERR, "Supported options: --host=<IP> and --port=<1-65535>\n");
                return 2;
            }
        }
        if ($port < 1 || $port > 65535) {
            fwrite(STDERR, "Port must be between 1 and 65535.\n");
            return 2;
        }
        $address = str_contains($host, ':') ? '[' . $host . ']' : $host;
        $documentRoot = $application->root() . '/public';
        $router = $application->root() . '/bootstrap/dev-server.php';
        if (!is_dir($documentRoot) || !is_file($router)) {
            fwrite(STDERR, "The public document root or development router is missing.\n");
            return 1;
        }

        echo "Development server: http://{$address}:{$port}\n";
        $process = proc_open(
            [PHP_BINARY, '-S', $address . ':' . $port, '-t', $documentRoot, $router],
            [STDIN, STDOUT, STDERR],
            $pipes,
            $application->root(),
            null,
            ['bypass_shell' => true],
        );
        if (!is_resource($process)) {
            fwrite(STDERR, "Unable to start the PHP development server.\n");
            return 1;
        }
        return proc_close($process);
    }

    private static function packageInstalled(string $package): bool
    {
        return class_exists(\Composer\InstalledVersions::class)
            && \Composer\InstalledVersions::isInstalled($package);
    }

    private static function installedVersion(string $package): string
    {
        if (!self::packageInstalled($package)) {
            return 'unpublished';
        }
        return \Composer\InstalledVersions::getPrettyVersion($package) ?? 'unpublished';
    }

    private static function unknown(string $command): int
    {
        fwrite(STDERR, "Unknown command: {$command}. Run 'php spark help'.\n");
        return 2;
    }
}
