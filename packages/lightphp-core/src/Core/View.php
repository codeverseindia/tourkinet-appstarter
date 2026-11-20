<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Core;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class View
{
    /** @var list<array{priority:int,path:string}> */
    private array $paths = [];

    public function addPath(string $path, int $priority = 0): void
    {
        $realPath = realpath($path);
        if ($realPath === false || !is_dir($realPath)) {
            throw new RuntimeException('Template directory does not exist.');
        }
        $this->paths[] = ['priority' => $priority, 'path' => $realPath];
        usort($this->paths, static fn(array $left, array $right): int => $left['priority'] <=> $right['priority']);
    }

    public function renderPage(string $template, array $data = []): string
    {
        return $this->render('layouts/header', $data)
            . $this->render($template, $data)
            . $this->render('layouts/footer', $data);
    }

    public function render(string $template, array $data = []): string
    {
        if (preg_match('#^[A-Za-z0-9][A-Za-z0-9/_-]*$#D', $template) !== 1) {
            throw new InvalidArgumentException('Invalid template name.');
        }

        $file = $this->find($template . '.php');
        if ($file === null) {
            throw new RuntimeException('Template was not found: ' . $template);
        }

        ob_start();
        try {
            (static function (string $__file, array $__data): void {
                extract($__data, EXTR_SKIP);
                require $__file;
            })($file, $data);
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }

    private function find(string $relativePath): ?string
    {
        foreach ($this->paths as $entry) {
            $candidate = $entry['path'] . '/' . $relativePath;
            $realPath = realpath($candidate);
            if ($realPath !== false && is_file($realPath) && str_starts_with($realPath, $entry['path'] . DIRECTORY_SEPARATOR)) {
                return $realPath;
            }
        }

        return null;
    }
}
