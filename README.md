# LightPHP App Starter

A server-rendered PHP 8.5 application built on `codeverseindia/lightphp-core` and `codeverseindia/tourkinet-travel-app`. It uses the Travel App homepage and templates.

## Requirements

- PHP 8.5+ with cURL, DOM, and JSON extensions
- Composer 2

## Setup

```sh
composer install
test -f .env || cp .env.example .env
php spark serve
```

Open `http://localhost:8080`. If `.env` already exists, keep it and update only the values you need.

Travel App routes include the homepage, packages, blogs, destinations, contact inquiries, and sitemap. Client-specific routes are registered in `config/route.php`.

The app uses the live Tour Kinet API by default. Configure `TOURKINET_API_KEY` in the private `.env` file to enable API-backed package and blog data. The legacy `TOURKINET_ACCESS_KEY` setting is also accepted when `TOURKINET_API_KEY` is not set. Without a key, pages still render their empty states. API requests stay server-side.

This repository is the starter source. After it is published on Packagist, create a new project with:

```sh
composer create-project codeverseindia/lightphp-app-starter my-app
```

## Commands

```sh
php spark help
php spark routes
php spark doctor
php spark serve --port=8081
composer test
```

The development server binds to loopback by default. `--host=<IP>` is available for intentional development-only network binding.

## Structure

- `app/Config/app.php` — application name, environment, storage, and security headers.
- `app/Config/website.php` — starter website identity and template theme.
- `app/Config/tourkinet.php` — API URL, endpoints, caching, and optional Turnstile settings.
- `app/Controllers/HomeController.php` — the welcome page controller.
- `app/Views/pages/blog-detail.php` — client-specific, safely formatted story reader.
- `app/Views/pages/welcome.php` — the starter welcome page.
- `config/route.php` — explicit client route registration.
- `public/` — the only web document root.
- `public/assets/css/app.css` — shared compact Bootstrap styling.
- `writable/` — private cache and log storage.

Add client controllers and views in `app/`, then register routes explicitly in `config/route.php`. Use `e()` for dynamic HTML output. Do not copy Core source into this project or edit files in `vendor/`.

Travel App's templates use Bootstrap 5 from the existing CDN integration; `app.css` keeps the shared pages compact and responsive. No frontend build tools or Bootstrap Composer dependency are added.

## Deployment

Configure the web server document root as `project-root/public/`. `public_html` is a symlink to `public/` for shared hosts that require that directory name; the host must allow symlink traversal. Never expose the repository root, `.env`, `vendor/`, or `writable/`.

Set `APP_ENV=production`, `APP_DEBUG=false`, a correct HTTPS `APP_URL`, and a valid timezone. Keep `writable/cache` and `writable/logs` private and writable by the PHP-FPM/Apache user.
