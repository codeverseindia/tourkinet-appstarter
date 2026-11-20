# LightPHP Core

LightPHP Core is the framework-independent PHP 8.5 application foundation used by the website starter and the Tour Kinet Travel App. It provides application bootstrap, request/response handling, explicit routing, template resolution, configuration, file caching, security primitives, HTTP transport, and the Spark CLI.

## Development

This package is developed in the parent starter repository. From the repository root, run:

```sh
composer install
php packages/lightphp-core/tests/run.php
```

The package uses PSR-4 autoloading under `TourKinet\LightPHP\`. It does not contain Tour Kinet endpoints, API credentials, travel controllers, or default travel website views.

## Release

The package directory is self-contained and has its own `composer.json`. To prepare a standalone repository, export the history under `packages/lightphp-core` (for example with `git subtree split --prefix=packages/lightphp-core`) so `composer.json` is at the repository root. Tag releases in that repository, such as `v1.0.0`; Composer package versions come from release tags, not a manifest `version` field.

Publish only after repository ownership, access, and package registry settings are explicitly approved. The website starter's local path repository is for monorepo development only.
