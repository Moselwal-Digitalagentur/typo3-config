# TYPO3 Config — Fluent Configuration Library

[![TYPO3 14](https://img.shields.io/badge/TYPO3-14.x-orange.svg)](https://get.typo3.org/)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Fluent PHP API for environment-specific TYPO3 configuration. Provides context-based presets, secure secret management, caching, logging, mailer setup, and TLS/mTLS auto-configuration.

## Features

- **Context-Based Presets** — Auto-configuration for Production, Development, CLI, and Testing environments
- **Secret Resolution Cascade** — Secure secret loading from `_FILE` env vars → `/run/secrets/` → `getenv()` → fallback
- **Caching Auto-Configuration** — Redis/Valkey (via `moselwal/keyvalue-store`), APCu, or file backends
- **TLS/mTLS Auto-Configuration** — Automatic certificate discovery for database and Redis connections
- **Mailer Setup** — SMTP and Mailpit configuration helpers
- **Logging Presets** — Context-aware logging configuration
- **Image Engine** — ImageMagick and GraphicsMagick configuration
- **Fluent Interface** — Chainable API for readable configuration
- **TYPO3 14.x** — getestet auf der aktuellen LTS-Linie

## Installation

```bash
composer require moselwal/typo3-config
```text

## Quick Start

In your `config/config.php`:

```php
<?php

use Moselwal\Config;

Config::initialize()
    ->loadCoreSecrets()
    ->loadMailSecrets()
    ->autoconfigureCaching();
```

## Secret Management

Secrets are resolved through a cascading lookup:

1. File path from env var (`DB_PASSWORD_FILE`)
2. Default secret file (`/run/secrets/db_password`)
3. Direct env var (`getenv('DB_PASSWORD')`)
4. Fallback parameter (optional)

```bash
# Docker secrets or Kubernetes mounts
echo "supersecret" > /run/secrets/db_password
```text

```php
Config::get()->loadCoreSecrets();
```

No secrets need to be committed to Git or stored in `.env` files.

## Available Presets

| Method | Description |
|--------|-------------|
| `applyDefaults()` | Auto-selects preset based on TYPO3 context |
| `useCliPreset()` | Optimized for CLI calls with debug output |
| `useDevelopmentPreset()` | Development environment settings |
| `useProductionPreset()` | Production with APP_ROOT (container) |
| `useProductionPresetVHost()` | Production with VHost-based setup |

## Usage Example

```php
Config::get()
    ->useGraphicsMagick()
    ->useMailpit()
    ->autoconfigureCaching()
    ->setPhpSettings([
        'memory_limit' => '512M',
        'max_execution_time' => 120,
    ])
    ->setConfigPathValues('SYS', [
        'defaultScheme' => 'https',
    ]);
```text

## TYPO3 Version Compatibility

| TYPO3 Version | Status |
|---------------|--------|
| v11 | Supported |
| v12 | Supported (`pagesection` cache auto-removed) |
| v13 | Supported (`imagesizes` cache auto-removed) |
| v14 | Supported |

## Architecture

```
src/
├── Config.php              # Main class (Singleton, Fluent API)
└── ConfigInterface.php     # Public contract interface
tests/
├── ConfigTestCase.php      # Base test class (singleton reset, $GLOBALS isolation)
├── TestableConfig.php      # Test subclass for version injection
└── ...                     # 35+ tests with >120 assertions
```text

- **Singleton pattern** via `Config::initialize()` / `Config::get()`
- **Namespace**: `Moselwal\` → `src/` (PSR-4)
- Late static binding (`new static()`) for project-specific extensions

## Development

```bash
composer install
composer test              # PHPUnit tests
composer test:coverage     # Tests with coverage report
composer phpstan           # PHPStan Level 5 static analysis
```

## Dependencies

| Package | Type | Purpose |
|---------|------|---------|
| `moselwal/keyvalue-store` | Optional | Redis/Valkey cache and session backends |
| `moselwal/dev` | Dev | Shared QA tooling |

## Related

- [keyvalue-store](../keyvalue-store) — Redis/Valkey integration used by the caching auto-configuration

## License

MIT — see [LICENSE](LICENSE) for details.
