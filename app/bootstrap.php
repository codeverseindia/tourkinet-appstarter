<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_HTML_PATH', ROOT_PATH . '/public_html');
define('STORAGE_PATH', ROOT_PATH . '/storage');

require APP_PATH . '/config.php';
load_environment(ROOT_PATH . '/.env');

$autoload = ROOT_PATH . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

$appConfig = require ROOT_PATH . '/config/app.php';
try {
    new DateTimeZone($appConfig['timezone']);
} catch (Throwable $exception) {
    throw new RuntimeException('Invalid application timezone configuration.', previous: $exception);
}
date_default_timezone_set($appConfig['timezone']);

require APP_PATH . '/helpers.php';
require APP_PATH . '/security.php';
require APP_PATH . '/validation.php';
require APP_PATH . '/cache.php';
require APP_PATH . '/api.php';
require APP_PATH . '/router.php';

if (PHP_SAPI !== 'cli') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('travel_session');
        session_set_cookie_params([
            'httponly' => true,
            'secure' => is_https(),
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        if (!session_start()) {
            throw new RuntimeException('Unable to start a secure session.');
        }
    }
}

set_exception_handler(static function (Throwable $exception): void {
    if (config('app.env') === 'production') {
        error_log('[application] Unhandled exception: ' . $exception::class);
    } else {
        error_log(sprintf('[application] %s: %s', $exception::class, $exception->getMessage()));
    }

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Error: ' . (config('app.debug') ? $exception->getMessage() : 'An unexpected error occurred.') . PHP_EOL);
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }
    if (config('app.debug') && config('app.env') !== 'production') {
        echo '<pre>' . e($exception->getMessage()) . "\n" . e($exception->getFile() . ':' . $exception->getLine()) . '</pre>';
        return;
    }
    try {
        view('500', ['title' => 'Server error']);
    } catch (Throwable) {
        echo 'An unexpected error occurred.';
    }
});

load_routes();
