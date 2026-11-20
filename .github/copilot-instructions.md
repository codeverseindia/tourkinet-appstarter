# Project instructions

- Use native PHP 8.5+ with strict typing and follow existing lightweight patterns.
- Use `codeverseindia/lightphp-core` as the application foundation. `codeverseindia/tourkinet-travel-app` is installed but not booted in this clean starter. Do not copy package source into this project or edit files under `vendor/`.
- Keep client route registration centralized in `config/route.php`; do not add controller discovery or dynamic URL-based includes.
- Preserve CSRF, validation, output escaping, security headers, private cache/log storage, and request-size limits.
- Use server-rendered PHP templates and escape dynamic output with `e()`.
- Keep `public/` as the only web document root; keep configuration and `writable/` private.
- Use Bootstrap 5 from the existing CDN integration. Do not introduce frontend build tools, databases, or dependencies without an actual requirement.
