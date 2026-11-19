# Lightweight Native PHP Travel Starter

A small, server-rendered travel website starter for PHP 8.5. It has no database or mandatory Composer packages and keeps application code, configuration and storage outside the public document root. Bootstrap 5.3.9 CSS and JavaScript are loaded from jsDelivr with Subresource Integrity; a network connection is required for Bootstrap styling and the collapsible mobile navigation.

## Requirements and local start

- PHP 8.5 with cURL and JSON extensions
- PHP's built-in server is for local development only

Copy `.env.example` to `.env`, then run from the project root:

```sh
php spark serve
php spark serve --port=8081
php spark serve --host=127.0.0.1 --port=9000
```

Visit `http://127.0.0.1:8080`. Static pages work without API configuration.
The optional `--host` value must be an IP address; the default bind address is loopback-only.

## CLI

| Command | Purpose |
| --- | --- |
| `php spark` or `php spark help` | Show commands and options |
| `php spark routes` | Show registered routes |
| `php spark cache:clear` | Remove valid application cache entries |
| `php tests/run.php` | Run lightweight CLI tests |

## Configuration and Tour Kinet

`.env` uses a restricted `KEY=value` syntax: uppercase key names, one value per line, optional matching single/double quotes, and full-line `#` comments. Do not put secrets in `public_html/`. `.env` is ignored by Git.

Set only `TOURKINET_API_URL` and `TOURKINET_ACCESS_KEY` from your account. The server-side adapter uses the documented v1 paths `/packages`, `/packages/{slug}`, `/blogs`, `/blogs/{slug}`, and `/inquiries`; keep endpoint changes centralized in `app/api.php`. Do not add a client-domain header or expose the access key.

API calls are server-side. Production URLs require HTTPS and TLS verification. In `APP_ENV=local`, HTTP is allowed only for `http://api.tourkinet.loc` and requests are pinned to loopback; private hosts and HTTP remain blocked in other environments. Local API media served from `http://app.tourkinet.loc/assets/` or `/storage/` is also allowed only in local mode. GET responses are cached under `storage/cache/`; public data may be served stale for `CACHE_MAX_STALE` seconds after an API failure. Missing credentials or failed requests return empty results rather than breaking static pages. Package and blog listings use the documented `data` array, pagination metadata, and query filters; detail pages render their documented package, image, itinerary, inclusion/exclusion, terms, and SEO fields. The package and blog API return only published public data. Package/contact forms submit the documented inquiry fields, with a package identifier for package inquiries and at least one contact method.

Never put an API key in templates, JavaScript or public assets. Inquiry submissions need the confirmed endpoint contract. Optional Turnstile validation is enabled by configuring both site and secret keys.

## Routes, views and branding

Register routes in `config/route.php` using the lightweight CI4-style route collection, for example `$routes->get('/packages/{slug}', 'package-detail')` or `$routes->post('/contact', 'submit-inquiry')`. Each handler must be explicitly handled by `handle_route()`; URLs never choose a PHP include. Views live in `views/`, use shared layouts, and must escape dynamic HTML with `e()`. Blog content is rendered as escaped plain text, not trusted HTML. Edit branding and contact data in `config/website.php`; `APP_NAME` and `APP_URL` supply the company name and site URL. Customize static page text in its matching template and design in `public_html/assets/css/style.css`.

## Production deployment

Set the web server document root to `project-root/public_html/` and keep `.env`, `app/`, `config/`, `views/`, `storage/`, `spark`, and `vendor/` outside that root. Ensure `storage/cache` and `storage/logs` are writable by the PHP-FPM/Apache user and not world-writable. Configure `APP_ENV=production`, `APP_DEBUG=false`, a correct HTTPS `APP_URL`, and a valid timezone. Use HTTPS at the web server.

### Nginx with PHP 8.5-FPM (Ubuntu)

Install and configure the PHP 8.5 FPM, cURL and JSON packages for your Ubuntu release, then use a site configuration like:

```nginx
server {
    listen 80;
    server_name example.com;
    root /srv/travel/current/public_html;
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

Configure TLS using your managed certificate, then redirect HTTP to HTTPS. Validate the FPM socket name against the installed package.

### Apache and shared hosting

Set `DocumentRoot` to the project's `public_html/` directory, enable `mod_rewrite`, and enable the provided `public_html/.htaccess`. A virtual host can use:

```apache
DocumentRoot /srv/travel/current/public_html
<Directory /srv/travel/current/public_html>
    AllowOverride All
    Require all granted
</Directory>
```

If shared hosting requires the project root to be the document root and does not allow the application/private files to be moved outside the web root or denied by server configuration, it cannot meet this starter's security boundary. Do not work around this by exposing private folders.

## Composer, cache and testing

The base app does not require Composer. When adding a package, run `composer install`; the optional `vendor/autoload.php` is detected automatically. Composer's PSR-4 mapping is prepared for future `App\\` classes.

Cache files are JSON, named by a SHA-256 key, written atomically, and never deserialized as PHP objects. Clear with `php spark cache:clear`; do not store secrets or user-specific data in public API caches.

## Security and troubleshooting

Keep PHP production errors off-screen; they are logged privately. Use valid HTTPS API URLs and verify endpoint paths/payloads against your Tour Kinet account documentation. A blank packages/blog page means API settings are missing, unavailable or need response-shape adaptation, not that static routes are down. Confirm cURL/JSON extensions and private storage permissions. Review the starter privacy text and actual data retention, consent, and legal requirements before launch.
