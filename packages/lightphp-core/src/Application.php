<?php
declare(strict_types=1);

namespace TourKinet\LightPHP;

use ErrorException;
use Throwable;
use TourKinet\LightPHP\Cache\FileCache;
use TourKinet\LightPHP\Core\Config;
use TourKinet\LightPHP\Core\Request;
use TourKinet\LightPHP\Core\Response;
use TourKinet\LightPHP\Core\Router;
use TourKinet\LightPHP\Core\View;
use TourKinet\LightPHP\Security\Csrf;
use TourKinet\LightPHP\Security\RateLimiter;
use TourKinet\LightPHP\Security\SecurityHeaders;
use TourKinet\LightPHP\Support\Helpers;
use TourKinet\LightPHP\Support\Runtime;

final class Application
{
    /** @var array<string, callable(): bool> */
    private array $doctorChecks = [];

    /** @var array<string, string> */
    private array $packageVersions = [];

    private readonly Config $config;
    private readonly Router $router;
    private readonly View $view;
    private readonly FileCache $cache;
    private readonly RateLimiter $rateLimiter;

    private function __construct(
        private readonly string $root,
        array $configFiles,
        string $configDirectory,
        string $viewsDirectory,
    )
    {
        Config::loadEnvironment($root . '/.env');
        $this->config = Config::fromProject($configDirectory, $configFiles);
        $this->router = new Router();
        $this->view = new View();
        if (is_dir($viewsDirectory)) {
            $this->view->addPath($viewsDirectory, 0);
        }
        $coreViews = dirname(__DIR__) . '/resources/views';
        if (is_dir($coreViews)) {
            $this->view->addPath($coreViews, 200);
        }
        $storage = (string) $this->config->get('app.storage_path', $root . '/storage');
        $this->cache = new FileCache(
            $storage,
            max(0, (int) $this->config->get('app.cache.max_stale', 0)),
            (bool) $this->config->get('app.cache.enabled', true),
        );
        $this->rateLimiter = new RateLimiter($storage);

        $timezone = (string) $this->config->get('app.timezone', 'UTC');
        if (!date_default_timezone_set($timezone)) {
            throw new \RuntimeException('Configured application timezone is invalid.');
        }
        Csrf::startSession($this->config, Request::fromGlobals());
        Runtime::setApplication($this);
        $this->registerErrorHandlers();
    }

    public static function boot(
        string $root,
        array $configFiles = ['app'],
        ?string $configDirectory = null,
        ?string $viewsDirectory = null,
    ): self
    {
        $realRoot = realpath($root);
        if ($realRoot === false || !is_dir($realRoot)) {
            throw new \RuntimeException('Application root does not exist.');
        }

        return new self(
            $realRoot,
            $configFiles,
            $configDirectory ?? $realRoot . '/config',
            $viewsDirectory ?? $realRoot . '/resources/views',
        );
    }

    public function handle(Request $request): Response
    {
        $maxBytes = max(0, (int) $this->config->get('app.max_request_bytes', 1048576));
        if ($maxBytes > 0 && $request->contentLength > $maxBytes) {
            return Response::text('Request body is too large.', 413);
        }

        try {
            $response = $this->router->dispatch($request);
            if ($response->status() === 404 && $response->body() === 'Not found.') {
                return $this->errorResponse(404);
            }

            return $response;
        } catch (Throwable $exception) {
            error_log(sprintf('[lightphp] Unhandled request exception: %s in %s:%d', $exception->getMessage(), $exception->getFile(), $exception->getLine()));
            return $this->errorResponse(500, $exception);
        }
    }

    public function run(): void
    {
        $request = Request::fromGlobals();
        $response = $this->handle($request);
        SecurityHeaders::send($this->config, Helpers::nonce(), $request->isSecure());
        $response->send();
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function view(): View
    {
        return $this->view;
    }

    public function cache(): FileCache
    {
        return $this->cache;
    }

    public function rateLimiter(): RateLimiter
    {
        return $this->rateLimiter;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function addDoctorCheck(string $name, callable $check): void
    {
        $this->doctorChecks[$name] = $check;
    }

    public function doctorChecks(): array
    {
        return $this->doctorChecks;
    }

    public function addPackageVersion(string $name, string $package): void
    {
        $version = 'unpublished';
        if (class_exists(\Composer\InstalledVersions::class) && \Composer\InstalledVersions::isInstalled($package)) {
            $version = \Composer\InstalledVersions::getPrettyVersion($package) ?? 'unpublished';
        }
        $this->packageVersions[$name] = $version;
    }

    public function packageVersions(): array
    {
        return $this->packageVersions;
    }

    private function registerErrorHandlers(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler(function (Throwable $exception): void {
            if (PHP_SAPI === 'cli') {
                error_log('[lightphp] Uncaught exception: ' . $exception->getMessage());
                fwrite(STDERR, "Application bootstrap failed.\n");
                exit(1);
            }
            error_log(sprintf('[lightphp] Uncaught exception: %s in %s:%d', $exception->getMessage(), $exception->getFile(), $exception->getLine()));
            $request = Request::fromGlobals();
            SecurityHeaders::send($this->config, Helpers::nonce(), $request->isSecure());
            $this->errorResponse(500, $exception)->send();
        });
    }

    private function errorResponse(int $status, ?Throwable $exception = null): Response
    {
        $template = $status === 404 ? 'errors/404' : 'errors/500';
        $data = ['title' => $status === 404 ? 'Page not found' : 'Server error'];
        if ($exception !== null && (bool) $this->config->get('app.debug', false)) {
            $data['details'] = $exception->getMessage();
        }
        try {
            return Response::html($this->view->renderPage($template, $data), $status);
        } catch (Throwable) {
            $message = $status === 404 ? 'The requested page could not be found.' : 'An unexpected error occurred.';
            if (isset($data['details'])) {
                $message .= ' ' . Helpers::escape($data['details']);
            }
            return Response::html('<!doctype html><html lang="en"><meta charset="utf-8"><title>' . Helpers::escape($data['title']) . '</title><h1>' . Helpers::escape($data['title']) . '</h1><p>' . $message . '</p></html>', $status);
        }
    }
}
