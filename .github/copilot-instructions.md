# Project instructions

- Use native PHP 8.5+ with strict typing and follow existing lightweight patterns.
- Keep the three layers independent: generic infrastructure belongs in `packages/lightphp-core`, Tour Kinet and travel behavior belongs in `packages/travel-app`, and client branding/routes/assets belong at the repository root.
- Keep Tour Kinet API communication centralized in `packages/travel-app/src/Api/TourKinetClient.php`; endpoint mappings live in the client site's `app/Config/tourkinet.php`.
- Keep client route registration centralized in `config/route.php`; do not add controller discovery or dynamic URL-based includes.
- Use Composer PSR-4 autoloading. Never duplicate package code or edit files under `vendor/`.
- Preserve CSRF, validation, output escaping, security headers, HTTPS verification, SSRF protections, private cache/log storage, and API credential secrecy.
- Use server-rendered PHP templates and escape dynamic output. Client templates under `app/Views/` override Travel App defaults.
- Keep `public/` as the only web document root; keep configuration and `writable/` private.
- Use Bootstrap 5 from the existing CDN integration. Do not introduce frameworks, frontend build tools, databases, or dependencies without an actual requirement.
- Preserve compatibility with the documented Tour Kinet API; do not guess endpoints or payload fields.
