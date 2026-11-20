# Tour Kinet Website Starter

A lightweight, server-rendered PHP 8.5 travel website organized as three layers:

1. `packages/lightphp-core` — reusable application foundation.
2. `packages/travel-app` — Tour Kinet integration and travel pages.
3. The repository root — client branding, configuration, routes, assets, and deployment.

The application has no database and no third-party PHP runtime dependencies. Bootstrap 5 is loaded from jsDelivr. Keep `public/` as the only web document root; `.env`, package sources, Composer files, and `writable/` are private.

The client application follows a CodeIgniter 4-inspired layout (`app/Config`, `app/Controllers`, `app/Views`, `public`, `tests`, and `spark`) but remains native PHP and does not install or emulate CodeIgniter. The one intentional convention difference is routing: client routes stay centralized in `config/route.php`.

## Requirements and local setup

- PHP 8.5+ with cURL and JSON extensions
- Composer 2
- PHP's built-in server for development only

The local path repositories symlink the package sources into `vendor/`; never edit files inside `vendor/`.

```sh
composer install
php spark
php spark serve
```

Open the printed local URL, currently `http://localhost:8080`, and set `APP_URL` to that client-site origin. The local API is separate: set `TOURKINET_API_URL=https://api.tourkinet.com/api/v1` only when developing against the local API. The client domain header is derived from `APP_URL` unless explicitly configured.

Pages render without API credentials. API-backed lists will display an empty state when data is unavailable; they do not invent package availability.

## Commands and tests

```sh
php spark help
php spark routes
php spark version
php spark doctor
php spark cache:clear
php spark serve --port=8081
composer test
```

The development server binds to loopback by default. `--host=<IP>` is available for intentional development-only network binding.
`php spark version` reads installed package versions from Composer metadata and displays `unpublished` if metadata is unavailable.

## Configuration and security

Copy `.env.example` to `.env` only when `.env` does not already exist. Keep the actual `.env` private and never commit API keys.

Configuration is split between:

- `app/Config/app.php` — application, storage, cache, and security headers.
- `app/Config/website.php` — website name, contact details, theme, and metadata.
- `app/Config/tourkinet.php` — API URL, endpoint mapping, cache TTLs, and optional Turnstile settings.

Set `TOURKINET_API_KEY` (or the legacy `TOURKINET_ACCESS_KEY`) server-side. The API client sends the key as a Bearer token and `X-Client-Domain` as metadata; it never exposes credentials to the browser. The existing endpoint mappings are `/packages`, `/packages/{slug}`, `/blogs`, `/blogs/{slug}`, and `/inquiries`. Do not change endpoint contracts without confirming them against Tour Kinet documentation.

Production API requests require HTTPS, TLS verification, and public DNS resolution. Local HTTP is limited to `api.tourkinet.loc` when `APP_ENV=local`. External API redirects are disabled. Package and blog responses may use the private file cache; inquiry submissions and personal data are never cached. CSRF, server-side validation, a honeypot, rate limiting, optional Turnstile, escaped output, request-size limits, and security headers are enabled by the application.

The blog detail template renders API content as escaped plain text, not trusted HTML. Privacy and terms pages are starter text; review and replace them before launch.

## Routes, views, and client customization

Travel routes are explicitly registered by `TourKinet\TravelApp\TravelApplication`. Client-specific routes are registered in `config/route.php`; duplicate routes are rejected unless the client explicitly opts into an override:

```php
$routes->get('/', $customHomeHandler, 'home', true);
```

For a new client page, register its route and render a named client template:

```php
$routes->get('/our-story', static function () use ($application): Response {
    $website = (array) $application->config()->get('website', []);
    $url = rtrim((string) $application->config()->get('app.url', ''), '/');

    return Response::html($application->view()->renderPage('pages/our-story', [
        'website' => $website,
        'title' => 'Our story',
        'description' => 'Learn about our travel company.',
        'canonical' => $url . '/our-story',
    ]));
}, 'client.our-story');
```

Add the corresponding `app/Views/pages/our-story.php`. Use `App\Controllers\` classes in `app/Controllers/` when a page needs more logic; register those handlers explicitly in `config/route.php`. The starter does not scan files or create routes from URL values.

Template resolution order is:

1. Client templates in `app/Views/`
2. Travel App defaults in `packages/travel-app/resources/views/`
3. Core error templates

For example, create `app/Views/pages/packages.php` to replace the default package list without editing package sources. Use the shared layouts or provide `app/Views/layouts/header.php` and `footer.php`. Escape dynamic HTML with `e()`.

Client CSS and images live in `public/assets/`. The public document root must never include package source, configuration, `vendor/`, or private storage.

## Composer development and production

The root `composer.json` is a development monorepo manifest. Its `packages/*` path repository pins local package metadata to `1.0.0` and symlinks the packages. `composer.lock` is committed for reproducible local installs.

### Creating a separate client project

The repository root is the working client-starter template. A client-owned project keeps its own `composer.json`, `.env.example`, `.gitignore`, `app/`, `bootstrap/`, `config/`, `public/`, `writable/`, `spark`, and website tests; it requires Core and Travel App through Composer and must not copy their source into `vendor/`. Do not copy the development `.env`.

For a local sibling project while developing in this workspace, copy only the client-owned files (not `packages/`, `vendor/`, or `.env`):

```sh
mkdir ../client-acme
cp -a app bootstrap config public tests spark .env.example .gitignore ../client-acme/
mkdir -p ../client-acme/writable/cache ../client-acme/writable/logs
```

Use the local path-repository example below, adjusting its relative `url` if the new project is not a sibling. This gives the client a separate working tree and Composer lockfile while sharing the authoritative package sources.

While developing against this unpublished monorepo, the client's `composer.json` can use a Composer `path` repository pointing to the local package checkout. Replace the example path with the relative path from the new client project to this monorepo:

```json
{
    "name": "acme/travel-website",
    "type": "project",
    "license": "proprietary",
    "repositories": [
        {
            "type": "path",
            "url": "../tripnexts/packages/*",
            "options": {
                "symlink": true,
                "versions": {
                    "codeverseindia/tourkinet-lightphp-core": "1.0.0",
                    "codeverseindia/tourkinet-travel-app": "1.0.0"
                }
            }
        }
    ],
    "require": {
        "php": "^8.5",
        "codeverseindia/tourkinet-lightphp-core": "^1.0",
        "codeverseindia/tourkinet-travel-app": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    },
    "scripts": {
        "serve": "@php spark serve",
        "routes": "@php spark routes",
        "cache-clear": "@php spark cache:clear",
        "test": "@php tests/run.php"
    },
    "config": {
        "sort-packages": true,
        "optimize-autoloader": true
    }
}
```

Run `composer update` once in that new project, review and commit its own `composer.lock`, then customize `app/Config/`, `app/Controllers/`, `app/Views/`, `config/route.php`, and `public/assets/`. The local path is machine/workspace-specific and should not be used in a distributable client project.

For production, remove the local `path` repository and require the packages from Packagist (after they have actually been published) or from approved VCS repositories. Do not copy package code into the client project or edit `vendor/`. These packages and this starter are not published, so one-click `composer create-project` is not available yet.

After both libraries are published and available to Composer, a production client manifest can use the default Packagist repository without local package paths:

```json
{
    "name": "acme/travel-website",
    "type": "project",
    "license": "proprietary",
    "require": {
        "php": "^8.5",
        "codeverseindia/tourkinet-lightphp-core": "^1.0",
        "codeverseindia/tourkinet-travel-app": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

Do not use this production manifest before those package names and versions are actually published.

### Independent library releases

Each package has its own manifest, namespace, tests, README, and license. To prepare standalone repositories, export `packages/lightphp-core` and `packages/travel-app` independently (for example with `git subtree split --prefix=packages/lightphp-core` and `git subtree split --prefix=packages/travel-app`) so each exported repository has its package `composer.json` at its root.

Tag each package repository independently (`v1.0.0`, `v1.0.1`, and so on). The package version comes from Git release tags; do not add a manifest `version` field. Release Core first, then release a Travel App version whose `^1.0` Core requirement is satisfied. Publishing, pushing, or creating repositories requires explicit approval.

## Production deployment

Configure the web server document root as `project-root/public/`. Keep `writable/cache` and `writable/logs` writable by the PHP-FPM/Apache user and private. Set `APP_ENV=production`, `APP_DEBUG=false`, a correct HTTPS `APP_URL`, and a valid timezone. Use HTTPS at the web server.

### Nginx with PHP-FPM

```nginx
server {
    listen 80;
    server_name example.com;
    root /srv/travel/current/public;
    index index.php;

    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(?:env|ini|log|sql|bak|dist|json|lock|md)$ { deny all; }
    location /assets/ {
        try_files $uri =404;
        expires 7d;
        add_header Cache-Control "public";
    }
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
    }
    location ~ \.php$ { return 404; }
}
```

Configure TLS and redirect HTTP to HTTPS. Validate the FPM socket name for the installed PHP version. Apache deployments should set `DocumentRoot` to `public/`, enable `mod_rewrite`, and use the provided `public/.htaccess`. Do not expose private project folders as a workaround for restrictive hosting.
