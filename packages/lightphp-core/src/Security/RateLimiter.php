<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Security;

use JsonException;

final class RateLimiter
{
    public function __construct(private readonly string $storageRoot)
    {
    }

    public function allow(string $scope, string $identity, int $limit, int $window): bool
    {
        $directory = rtrim($this->storageRoot, DIRECTORY_SEPARATOR) . '/logs/rate-limit';
        if (is_link($this->storageRoot) || is_link(dirname($directory)) || is_link($directory)) {
            error_log('[lightphp] Rate-limit storage path verification failed.');
            return false;
        }
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            error_log('[lightphp] Unable to create rate-limit storage.');
            return false;
        }
        $file = $directory . '/' . hash('sha256', $scope . "\0" . $identity) . '.json';
        if (is_link($file)) {
            error_log('[lightphp] Rate-limit state path verification failed.');
            return false;
        }
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            error_log('[lightphp] Unable to open rate-limit storage.');
            return false;
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }
            $state = json_decode((string) stream_get_contents($handle), true);
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
            chmod($file, 0600);
            return true;
        } catch (JsonException) {
            error_log('[lightphp] Unable to write rate-limit state.');
            return false;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
