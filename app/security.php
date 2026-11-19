<?php
declare(strict_types=1);

function send_security_headers(): void
{
    $nonce = csp_nonce();
    $imageSources = config('app.env') === 'local' ? "'self' https: http://app.tourkinet.loc data:" : "'self' https: data:";
    header("Content-Security-Policy: default-src 'self'; img-src {$imageSources}; style-src 'self' https://cdn.jsdelivr.net; script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://challenges.cloudflare.com; frame-src https://challenges.cloudflare.com; connect-src 'self' https://challenges.cloudflare.com; form-action 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Frame-Options: DENY');
    if (config('app.env') === 'production' && is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('A session is required for CSRF protection.');
    }
    if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_verify(string $token): bool
{
    $expected = $_SESSION['_csrf'] ?? null;
    return is_string($expected) && $token !== '' && hash_equals($expected, $token);
}

function rate_limit_allow(string $scope, string $identity, int $limit, int $window): bool
{
    $directory = STORAGE_PATH . '/logs/rate-limit';
    if (is_link(STORAGE_PATH) || is_link(STORAGE_PATH . '/logs') || is_link($directory)) {
        error_log('[application] Rate-limit storage path verification failed.');
        return false;
    }
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        error_log('[application] Unable to create rate-limit storage.');
        return false;
    }
    $file = $directory . '/' . hash('sha256', $scope . "\0" . $identity) . '.json';
    $handle = fopen($file, 'c+');
    if ($handle === false) {
        error_log('[application] Unable to open rate-limit storage.');
        return false;
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            return false;
        }
        $raw = stream_get_contents($handle);
        $state = is_string($raw) ? json_decode($raw, true) : null;
        $now = time();
        if (!is_array($state) || !isset($state['start'], $state['count']) || $now - (int) $state['start'] >= $window) {
            $state = ['start' => $now, 'count' => 0];
        }
        if ((int) $state['count'] >= $limit) {
            return false;
        }
        $state['count']++;
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($state, JSON_THROW_ON_ERROR));
        fflush($handle);
        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function verify_turnstile(string $token): bool
{
    $secret = (string) env('TURNSTILE_SECRET_KEY', '');
    if ($secret === '' || $token === '' || !function_exists('curl_init')) {
        return false;
    }
    $handle = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    if ($handle === false) {
        return false;
    }
    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    if (!is_string($body) || $status !== 200) {
        return false;
    }
    $response = json_decode($body, true);
    $expectedHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
    $responseHost = strtolower((string) ($response['hostname'] ?? ''));
    return is_array($response)
        && ($response['success'] ?? false) === true
        && $expectedHost !== ''
        && $responseHost === $expectedHost;
}
