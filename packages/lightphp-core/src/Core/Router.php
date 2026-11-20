<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Core;

final class Router
{
    /** @var list<array{method:string, pattern:string, regex:string, handler:callable, name:?string, score:int}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler, ?string $name = null, bool $override = false, array $constraints = []): self
    {
        return $this->add('GET', $pattern, $handler, $name, $override, $constraints);
    }

    public function post(string $pattern, callable $handler, ?string $name = null, bool $override = false, array $constraints = []): self
    {
        return $this->add('POST', $pattern, $handler, $name, $override, $constraints);
    }

    public function dispatch(Request $request): Response
    {
        if (strlen($request->path) > 2048 || preg_match('/%(?![0-9A-Fa-f]{2})|[\x00-\x20\x7f]/', $request->path)) {
            return Response::text('Bad request.', 400);
        }
        $path = rawurldecode($request->path);
        if (str_contains($path, "\0") || preg_match('#(?:^|/)\.{1,2}(?:/|$)#', $path)) {
            return Response::text('Not found.', 404);
        }
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $allowedMethods = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }
            $allowedMethods[] = $route['method'];
            if ($route['method'] !== $request->method) {
                continue;
            }
            $params = array_filter($matches, static fn($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
            $response = ($route['handler'])($request, $params);
            if (!$response instanceof Response) {
                throw new \UnexpectedValueException('Route handlers must return a Response.');
            }
            return $response;
        }

        if ($allowedMethods !== []) {
            return new Response('Method not allowed.', 405, ['Allow' => implode(', ', array_unique($allowedMethods))]);
        }
        return Response::text('Not found.', 404);
    }

    public function urlFor(string $name, array $params = [], array $query = []): string
    {
        foreach ($this->routes as $route) {
            if ($route['name'] !== $name) {
                continue;
            }
            $url = preg_replace_callback(
                '/\{([a-z][a-z0-9_]*)\}/',
                static function (array $match) use ($params): string {
                    if (!isset($params[$match[1]]) || !is_scalar($params[$match[1]])) {
                        throw new \InvalidArgumentException('A route parameter is missing.');
                    }
                    return rawurlencode((string) $params[$match[1]]);
                },
                $route['pattern'],
            );
            return $query === [] ? (string) $url : (string) $url . '?' . http_build_query($query);
        }

        throw new \InvalidArgumentException('Named route was not registered.');
    }

    public function all(): array
    {
        return array_map(
            static fn(array $route): array => ['method' => $route['method'], 'uri' => $route['pattern'], 'name' => $route['name']],
            $this->routes,
        );
    }

    private function add(string $method, string $pattern, callable $handler, ?string $name, bool $override, array $constraints): self
    {
        if ($pattern !== '/' && preg_match('#^/(?:[A-Za-z0-9._~-]+|\{[a-z][a-z0-9_]*\})(?:/(?:[A-Za-z0-9._~-]+|\{[a-z][a-z0-9_]*\}))*$#D', $pattern) !== 1) {
            throw new \InvalidArgumentException('Invalid route pattern.');
        }
        if ($name !== null && preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $name) !== 1) {
            throw new \InvalidArgumentException('Invalid route name.');
        }
        if ($name !== null) {
            foreach ($this->routes as $route) {
                if ($route['name'] === $name && ($route['method'] !== $method || $route['pattern'] !== $pattern)) {
                    throw new \LogicException('A route is already registered with this name.');
                }
            }
        }
        foreach ($this->routes as $index => $route) {
            if ($route['method'] !== $method || $route['pattern'] !== $pattern) {
                continue;
            }
            if (!$override) {
                throw new \LogicException('A route is already registered for this method and path.');
            }
            unset($this->routes[$index]);
            break;
        }

        $quoted = preg_quote($pattern, '#');
        $regex = preg_replace_callback(
            '/\\\\\{([a-z][a-z0-9_]*)\\\\\}/',
            static function (array $match) use ($constraints): string {
                $expression = $constraints[$match[1]] ?? '[A-Za-z0-9][A-Za-z0-9._-]{0,119}';
                if (!is_string($expression) || @preg_match('#^(?:' . $expression . ')$#D', '') === false) {
                    throw new \InvalidArgumentException('Invalid route constraint.');
                }
                return '(?P<' . $match[1] . '>' . $expression . ')';
            },
            $quoted,
        );
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '/?$#D',
            'handler' => $handler,
            'name' => $name,
            'score' => strlen(preg_replace('/\{[a-z][a-z0-9_]*\}/', '', $pattern) ?: ''),
        ];
        usort($this->routes, static fn(array $left, array $right): int => $right['score'] <=> $left['score']);

        return $this;
    }
}
