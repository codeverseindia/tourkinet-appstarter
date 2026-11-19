# Project rules

- Use Native PHP 8.5 with strict typing.
- Follow the existing lightweight architecture; do not introduce a framework.
- Do not add dependencies or files unnecessarily.
- Reuse existing helpers and keep routing centralized in `config/routes.php`.
- Keep Tour Kinet API communication centralized in `app/api.php`.
- Preserve CSRF, validation, output escaping, security headers and private storage protections.
- Use server-rendered PHP templates and escape dynamic output.
- Keep `public_html/` as the only web document root; keep configuration and storage private.
- Avoid frontend build tools and third-party frontend libraries.
- Keep code compact, readable and backward-compatible where practical.
- Do not guess undocumented Tour Kinet endpoint contracts or expose API credentials.
