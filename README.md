# LightPHP App Starter

A minimal, server-rendered PHP 8.5 project built on `codeverseindia/lightphp-core`. It also installs `codeverseindia/tourkinet-travel-app` as a Composer dependency, but keeps the starter itself clean: only the Core is booted, with a single welcome page and explicit route.

## Requirements

- PHP 8.5+ with cURL and JSON extensions
- Composer 2

## Setup

```sh
composer install
test -f .env || cp .env.example .env
php spark serve
```

Open `http://localhost:8080`. If `.env` already exists, keep it and update only the values you need.

The current Travel App v1.0.0 release requires the old Core `^1.0` namespace. This starter therefore locks both packages to their updated `dev-main` branches, which use `CodeverseIndia\LightPHP`. The Travel App is installed but its routes and API configuration are not registered in the starter. Switch to compatible stable releases when they are published.

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
- `app/Controllers/HomeController.php` — the welcome page controller.
- `app/Views/pages/welcome.php` — the starter welcome page.
- `config/route.php` — explicit client route registration.
- `public/` — the only web document root.
- `writable/` — private cache and log storage.

Add client controllers and views in `app/`, then register routes explicitly in `config/route.php`. Use `e()` for dynamic HTML output. Do not copy Core source into this project or edit files in `vendor/`.

The welcome page uses Bootstrap 5 CSS from the existing CDN integration. It does not add a Composer dependency or load Bootstrap JavaScript.

## Deployment

Configure the web server document root as `project-root/public/`. `public_html` is a symlink to `public/` for shared hosts that require that directory name; the host must allow symlink traversal. Never expose the repository root, `.env`, `vendor/`, or `writable/`.

Set `APP_ENV=production`, `APP_DEBUG=false`, a correct HTTPS `APP_URL`, and a valid timezone. Keep `writable/cache` and `writable/logs` private and writable by the PHP-FPM/Apache user.
