<?php
declare(strict_types=1);

function cache_directory(): string
{
    return STORAGE_PATH . '/cache';
}

function cache_path(string $key): string
{
    return cache_directory() . '/' . hash('sha256', $key) . '.json';
}

function cache_read(string $key): array|null
{
    $file = cache_path($key);
    if (!is_file($file) || !is_readable($file)) {
        return null;
    }
    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded) || !isset($decoded['expires'], $decoded['stale_until']) || !array_key_exists('value', $decoded)) {
        return null;
    }
    return $decoded;
}

function cache_get(string $key): mixed
{
    if (!env('CACHE_ENABLED', true)) {
        return null;
    }
    $entry = cache_read($key);
    return $entry !== null && (int) $entry['expires'] >= time() ? $entry['value'] : null;
}

function cache_get_stale(string $key): mixed
{
    if (!env('CACHE_ENABLED', true)) {
        return null;
    }
    $entry = cache_read($key);
    return $entry !== null && (int) $entry['stale_until'] >= time() ? $entry['value'] : null;
}

function cache_set(string $key, mixed $value, int $ttl): bool
{
    if (!env('CACHE_ENABLED', true) || $ttl < 1) {
        return false;
    }
    $directory = cache_directory();
    if (is_link(STORAGE_PATH) || is_link($directory)) {
        error_log('[application] Cache storage path must not be a symbolic link.');
        return false;
    }
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        error_log('[application] Unable to create cache directory.');
        return false;
    }
    try {
        $encoded = json_encode([
            'expires' => time() + $ttl,
            'stale_until' => time() + $ttl + max(0, (int) env('CACHE_MAX_STALE', 3600)),
            'value' => $value,
        ], JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return false;
    }
    $target = cache_path($key);
    $temp = $directory . '/tmp-' . hash('sha256', $key) . '-' . bin2hex(random_bytes(6));
    if (file_put_contents($temp, $encoded, LOCK_EX) === false) {
        return false;
    }
    chmod($temp, 0600);
    if (!rename($temp, $target)) {
        unlink($temp);
        return false;
    }
    return true;
}

function cache_delete(string $key): bool
{
    $file = cache_path($key);
    return !is_file($file) || unlink($file);
}

function cache_clear(): bool
{
    if (is_link(STORAGE_PATH) || is_link(cache_directory())) {
        error_log('[application] Cache storage path verification failed.');
        return false;
    }
    $directory = realpath(cache_directory());
    if ($directory === false) {
        return !file_exists(cache_directory());
    }
    $storage = realpath(STORAGE_PATH);
    if ($storage === false || $directory !== $storage . DIRECTORY_SEPARATOR . 'cache') {
        error_log('[application] Cache directory path verification failed.');
        return false;
    }
    $success = true;
    try {
        foreach (new DirectoryIterator($directory) as $entry) {
            if ($entry->isDot() || !$entry->isFile() || $entry->isLink()) {
                continue;
            }
            $name = $entry->getFilename();
            if (!preg_match('/^(?:[a-f0-9]{64}\.json|tmp-[a-f0-9]{64}-[a-f0-9]{12})$/D', $name)) {
                continue;
            }
            $path = $entry->getRealPath();
            if ($path === false || !str_starts_with($path, $directory . DIRECTORY_SEPARATOR) || !unlink($path)) {
                $success = false;
            }
        }
    } catch (UnexpectedValueException) {
        error_log('[application] Unable to read cache directory.');
        return false;
    }
    return $success;
}
