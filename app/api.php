<?php
declare(strict_types=1);

const TOURKINET_ENDPOINTS = [
    'packages' => '/packages',
    'package' => '/packages/{slug}',
    'blogs' => '/blogs',
    'blog' => '/blogs/{slug}',
    'inquiry' => '/inquiries',
];

function api_is_local_development_url(array $parts): bool
{
    return (string) env('APP_ENV', 'local') === 'local'
        && strtolower((string) ($parts['scheme'] ?? '')) === 'http'
        && strtolower((string) ($parts['host'] ?? '')) === 'api.tourkinet.loc'
        && (!isset($parts['port']) || (int) $parts['port'] === 80);
}

function api_request(string $method, string $path, array $query = [], ?array $payload = null, int $ttl = 0, array $pathParams = []): array
{
    $base = trim((string) env('TOURKINET_API_URL', ''));
    $key = trim((string) env('TOURKINET_ACCESS_KEY', ''));
    foreach ($pathParams as $name => $value) {
        $path = str_replace('{' . $name . '}', rawurlencode((string) $value), $path);
    }
    if ($base === '' || $path === '') {
        return [];
    }
    $baseParts = parse_url($base);
    if (!is_array($baseParts)) {
        error_log('[application] Invalid Tour Kinet API base URL configuration.');
        return [];
    }
    $scheme = strtolower((string) ($baseParts['scheme'] ?? ''));
    $host = strtolower((string) ($baseParts['host'] ?? ''));
    $localDevelopment = api_is_local_development_url($baseParts);
    if (($scheme !== 'https' && !$localDevelopment) || $host === '' || isset($baseParts['user']) || isset($baseParts['pass']) || isset($baseParts['query']) || isset($baseParts['fragment'])) {
        error_log('[application] Invalid Tour Kinet API base URL configuration.');
        return [];
    }
    if (!preg_match('#^/[A-Za-z0-9._~/-]+$#D', $path) || str_starts_with($path, '//') || str_contains($path, '..')) {
        error_log('[application] Invalid Tour Kinet API endpoint configuration.');
        return [];
    }
    if (preg_match('/[\x00-\x20\x7f]/', $key)) {
        error_log('[application] Invalid Tour Kinet API key configuration.');
        return [];
    }
    $port = (int) ($baseParts['port'] ?? ($localDevelopment ? 80 : 443));
    if ($port < 1 || $port > 65535) {
        error_log('[application] Invalid Tour Kinet API port configuration.');
        return [];
    }
    $url = rtrim($base, '/') . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }
    $cacheKey = 'tourkinet:' . hash('sha256', $method . ':' . $url);
    if ($method === 'GET' && $ttl > 0) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }
    if ($key === '') {
        return $method === 'GET' ? (cache_get_stale($cacheKey) ?? []) : [];
    }
    $resolvedIp = $localDevelopment ? '127.0.0.1' : api_public_ip($host);
    if ($resolvedIp === null) {
        error_log('[application] Tour Kinet API host did not resolve to a public address.');
        return $method === 'GET' ? (cache_get_stale($cacheKey) ?? []) : [];
    }
    if (!function_exists('curl_init')) {
        error_log('[application] PHP cURL extension is unavailable.');
        return $method === 'GET' ? (cache_get_stale($cacheKey) ?? []) : [];
    }
    $handle = curl_init($url);
    if ($handle === false) {
        return $method === 'GET' ? (cache_get_stale($cacheKey) ?? []) : [];
    }
    $headers = [
        'Authorization: Bearer ' . $key,
        'Accept: application/json',
    ];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $headers,
    ];
    if (filter_var($host, FILTER_VALIDATE_IP) === false) {
        $curlIp = str_contains($resolvedIp, ':') ? '[' . $resolvedIp . ']' : $resolvedIp;
        $options[CURLOPT_RESOLVE] = [$host . ':' . $port . ':' . $curlIp];
    }
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_HTTPHEADER] = $headers;
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($payload ?? [], JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($options[CURLOPT_POSTFIELDS])) {
            curl_close($handle);
            return [];
        }
    }
    curl_setopt_array($handle, $options);
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    if ($body === false) {
        error_log('[application] Tour Kinet API request failed.');
    }
    curl_close($handle);
    $response = is_string($body) ? json_decode($body, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($response)) {
        error_log('[application] Tour Kinet API returned an unsuccessful response (HTTP ' . $status . ').');
        return $method === 'GET' ? (cache_get_stale($cacheKey) ?? []) : [];
    }
    if ($method === 'GET' && $ttl > 0) {
        cache_set($cacheKey, $response, $ttl);
    }
    return $response;
}

function getPackages(array $filters = []): array
{
    $ttl = $filters === [] ? (int) env('CACHE_PACKAGES_TTL', 900) : 0;
    return api_request('GET', TOURKINET_ENDPOINTS['packages'], $filters, null, $ttl);
}

function getPackage(string $slug): array
{
    $slug = valid_slug($slug) ?? '';
    if ($slug === '') {
        return [];
    }
    $response = api_request('GET', TOURKINET_ENDPOINTS['package'], [], null, (int) env('CACHE_PACKAGES_TTL', 900), ['slug' => $slug]);
    return api_single_item($response, $slug);
}

function getFeaturedPackages(): array
{
    return getPackages(['is_featured' => 'true', 'per_page' => 3]);
}

function getBlogs(array $filters = []): array
{
    $ttl = $filters === [] ? (int) env('CACHE_BLOGS_TTL', 1800) : 0;
    return api_request('GET', TOURKINET_ENDPOINTS['blogs'], $filters, null, $ttl);
}

function getBlog(string $slug): array
{
    $slug = valid_slug($slug) ?? '';
    if ($slug === '') {
        return [];
    }
    $response = api_request('GET', TOURKINET_ENDPOINTS['blog'], [], null, (int) env('CACHE_BLOGS_TTL', 1800), ['slug' => $slug]);
    return api_single_item($response, $slug);
}

function api_public_ip(string $host): ?string
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

function submitInquiry(array $data): array
{
    if (!rate_limit_allow('api-inquiry', (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 5, 600)) {
        return ['success' => false];
    }
    $response = api_request('POST', TOURKINET_ENDPOINTS['inquiry'], [], $data);
    return ['success' => $response !== [] && ($response['success'] ?? true) !== false, 'data' => $response];
}

function api_single_item(array $response, string $slug): array
{
    if (isset($response['data']) && is_array($response['data']) && !array_is_list($response['data'])) {
        $item = $response['data'];
        if (isset($item['package']) && is_array($item['package'])) {
            $package = $item['package'];
            return ($package['slug'] ?? null) === $slug || ($package['package_hash'] ?? null) === $slug ? $item : [];
        }
        return !isset($item['slug']) || $item['slug'] === $slug ? $item : [];
    }
    foreach (api_items($response) as $item) {
        if (is_array($item) && ($item['slug'] ?? null) === $slug) {
            return $item;
        }
    }
    return [];
}
