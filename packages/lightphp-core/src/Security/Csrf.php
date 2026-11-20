<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Security;

use RuntimeException;
use TourKinet\LightPHP\Core\Config;
use TourKinet\LightPHP\Core\Request;

final class Csrf
{
    public static function startSession(Config $config, Request $request): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name((string) $config->get('app.session_name', 'lightphp_session'));
        session_set_cookie_params([
            'httponly' => true,
            'secure' => $request->isSecure(),
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        if (!session_start()) {
            throw new RuntimeException('Unable to start a secure session.');
        }
    }

    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('A session is required for CSRF protection.');
        }
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function verify(string $token): bool
    {
        $expected = $_SESSION['_csrf'] ?? null;
        return is_string($expected) && $token !== '' && hash_equals($expected, $token);
    }
}
