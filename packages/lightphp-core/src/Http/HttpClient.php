<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Http;

use InvalidArgumentException;
use RuntimeException;

final class HttpClient
{
    public function request(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null,
        int $timeout = 8,
        ?string $allowedLocalHost = null,
    ): HttpResponse {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is unavailable.');
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Invalid HTTP URL.');
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) $parts['host']);
        $port = (int) ($parts['port'] ?? ($scheme === 'http' ? 80 : 443));
        $local = $allowedLocalHost !== null
            && (string) \env('APP_ENV', 'local') === 'local'
            && $scheme === 'http'
            && $host === strtolower($allowedLocalHost)
            && $port === 80;
        if (($scheme !== 'https' && !$local) || $port < 1 || $port > 65535) {
            throw new InvalidArgumentException('HTTP requests require HTTPS or an explicitly allowed local development host.');
        }
        if (!in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new InvalidArgumentException('Unsupported HTTP method.');
        }
        foreach ($headers as $header) {
            if (!is_string($header) || preg_match('/[\r\n]/', $header)) {
                throw new InvalidArgumentException('Invalid HTTP header.');
            }
        }
        $ip = $local ? '127.0.0.1' : self::publicAddress($host);
        if ($ip === null) {
            throw new RuntimeException('HTTP host did not resolve to a public address.');
        }

        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Unable to initialize the HTTP client.');
        }
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(5, max(1, $timeout)),
            CURLOPT_TIMEOUT => max(1, $timeout),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROXY => '',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ];
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            $resolveAddress = str_contains($ip, ':') ? '[' . $ip . ']' : $ip;
            $options[CURLOPT_RESOLVE] = [$host . ':' . $port . ':' . $resolveAddress];
        }
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($handle, $options);
        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = $result === false ? 'HTTP request failed.' : null;
        curl_close($handle);

        return new HttpResponse($status, is_string($result) ? $result : '', $error);
    }

    private static function publicAddress(string $host): ?string
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, $flags) !== false ? $host : null;
        }
        if (preg_match('/^(?=.{1,253}$)(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)(?:\.(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?))*$/D', $host) !== 1) {
            return null;
        }
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (!is_array($records) || $records === []) {
            return null;
        }
        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $addresses[] = $address;
            }
        }
        if ($addresses === []) {
            return null;
        }
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, $flags) === false) {
                return null;
            }
        }

        return $addresses[0];
    }
}
