<?php
declare(strict_types=1);

$documentRoot = realpath(dirname(__DIR__) . '/public');
$uriPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
if ($documentRoot === false || str_contains(rawurldecode($uriPath), "\0") || preg_match('/%(?![0-9A-Fa-f]{2})/', $uriPath)) {
    http_response_code(400);
    return true;
}

$decoded = rawurldecode($uriPath);
$candidate = realpath($documentRoot . DIRECTORY_SEPARATOR . ltrim($decoded, '/'));
if ($candidate !== false && str_starts_with($candidate, $documentRoot . DIRECTORY_SEPARATOR) && is_file($candidate)) {
    $types = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'text/javascript; charset=UTF-8',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'txt' => 'text/plain; charset=UTF-8',
        'xml' => 'application/xml; charset=UTF-8',
    ];
    $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
    if (isset($types[$extension])) {
        header('Content-Type: ' . $types[$extension]);
        header('X-Content-Type-Options: nosniff');
        readfile($candidate);
        return true;
    }
}

require $documentRoot . '/index.php';
return true;
