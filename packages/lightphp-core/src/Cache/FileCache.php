<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Cache;

use DirectoryIterator;
use JsonException;
use UnexpectedValueException;

final class FileCache
{
    public function __construct(
        private readonly string $storageRoot,
        private readonly int $maxStale = 0,
        private readonly bool $enabled = true,
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->read($key, 'expires');
    }

    public function getStale(string $key): mixed
    {
        return $this->read($key, 'stale_until');
    }

    public function set(string $key, mixed $value, int $ttl): bool
    {
        if (!$this->enabled || $ttl < 1) {
            return false;
        }
        $directory = $this->directory();
        if (is_link($this->storageRoot) || is_link($directory)) {
            error_log('[lightphp] Cache storage path must not be a symbolic link.');
            return false;
        }
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            error_log('[lightphp] Unable to create cache directory.');
            return false;
        }
        try {
            $encoded = json_encode([
                'expires' => time() + $ttl,
                'stale_until' => time() + $ttl + max(0, $this->maxStale),
                'value' => $value,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        $target = $this->path($key);
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

    public function delete(string $key): bool
    {
        $file = $this->path($key);
        return !is_file($file) || unlink($file);
    }

    public function clear(): bool
    {
        $directory = $this->directory();
        if (is_link($this->storageRoot) || is_link($directory)) {
            error_log('[lightphp] Cache directory path verification failed.');
            return false;
        }
        $realDirectory = realpath($directory);
        if ($realDirectory === false) {
            return !file_exists($directory);
        }
        $realStorage = realpath($this->storageRoot);
        if ($realStorage === false || $realDirectory !== $realStorage . DIRECTORY_SEPARATOR . 'cache') {
            error_log('[lightphp] Cache directory path verification failed.');
            return false;
        }

        $success = true;
        try {
            foreach (new DirectoryIterator($realDirectory) as $entry) {
                if ($entry->isDot() || !$entry->isFile() || $entry->isLink()) {
                    continue;
                }
                $name = $entry->getFilename();
                if (!preg_match('/^(?:[a-f0-9]{64}\.json|tmp-[a-f0-9]{64}-[a-f0-9]{12})$/D', $name)) {
                    continue;
                }
                $path = $entry->getRealPath();
                if ($path === false || !str_starts_with($path, $realDirectory . DIRECTORY_SEPARATOR) || !unlink($path)) {
                    $success = false;
                }
            }
        } catch (UnexpectedValueException) {
            error_log('[lightphp] Unable to read cache directory.');
            return false;
        }

        return $success;
    }

    private function read(string $key, string $expiry): mixed
    {
        if (!$this->enabled) {
            return null;
        }
        $file = $this->path($key);
        if (!is_file($file)) {
            return null;
        }
        if (!is_readable($file) || is_link($file)) {
            error_log('[lightphp] Cache entry is not safely readable.');
            return null;
        }
        $contents = file_get_contents($file);
        if (!is_string($contents)) {
            error_log('[lightphp] Unable to read cache entry.');
            return null;
        }
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            error_log('[lightphp] Corrupted cache entry was ignored.');
            return null;
        }
        if (!is_array($decoded) || !isset($decoded[$expiry]) || !array_key_exists('value', $decoded)) {
            error_log('[lightphp] Invalid cache entry was ignored.');
            return null;
        }
        if ((int) $decoded[$expiry] < time()) {
            return null;
        }

        return $decoded['value'];
    }

    private function path(string $key): string
    {
        return $this->directory() . '/' . hash('sha256', $key) . '.json';
    }

    private function directory(): string
    {
        return rtrim($this->storageRoot, DIRECTORY_SEPARATOR) . '/cache';
    }
}
