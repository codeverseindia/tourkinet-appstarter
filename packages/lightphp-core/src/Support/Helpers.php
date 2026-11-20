<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Support;

final class Helpers
{
    public static function escape(mixed $value): string
    {
        return htmlspecialchars(self::text($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    public static function safeHttpsUrl(mixed $value): ?string
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $parts = parse_url($value);
        if (!is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https' ? $value : null;
    }

    public static function jsonLd(array $data): string
    {
        return '<script type="application/ld+json" nonce="' . self::escape(self::nonce()) . '">'
            . json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)
            . '</script>';
    }

    public static function nonce(): string
    {
        $GLOBALS['lightphp_csp_nonce'] ??= base64_encode(random_bytes(18));
        return $GLOBALS['lightphp_csp_nonce'];
    }
}
