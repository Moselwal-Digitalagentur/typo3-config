# typo3-config – Zentrale Konfigurationsbasis für TYPO3-Projekte

**Moselwal Digitalagentur GmbH – Konfigurationen, die funktionieren.**

Dieses Paket stellt eine zentrale Klasse `\Moselwal\Config` bereit, mit der TYPO3-Projekte automatisiert, sicher und effizient vorkonfiguriert werden können. Es unterstützt kontextspezifische Presets, sicheres Secret-Management, Logging, Caching, Mailer-Setups und vieles mehr.

---

## Features

- Automatische Konfiguration für:
    - TYPO3 Caches (Redis/Valkey via `moselwal/keyvalue-store`, APCu)
    - Mailer (SMTP, Mailpit)
    - Logging (kontextabhängig)
    - Image-Engines (ImageMagick, GraphicsMagick)
    - PHP Settings
- Presets für `Production`, `CLI`, `Development` & `Testing`
- Sichere `resolveSecret()`-Kaskade (ENV, Dateien, Docker Secrets, Fallbacks)
- TLS/mTLS Auto-Konfiguration für Datenbank und Redis/Valkey
- Fluent Interface – einfach & lesbar
- Vollständiges `ConfigInterface` mit allen öffentlichen Methoden
- TYPO3 v11, v12, v13 und v14 kompatibel (automatische Versionsweichen)

---

## Installation

Mit Composer installieren:

```bash
composer require moselwal/typo3-config
```

---

## Beispiel: Einbindung in config/config.php

```php
<?php

use Moselwal\Config;

Config::initialize()
    ->loadCoreSecrets()
    ->loadMailSecrets()
    ->autoconfigureCaching();
```

---

## Secrets in /run/secrets/ nutzen

Lege deine Secrets in Dateien ab (z.B. durch Docker Secrets oder Kubernetes Mounts):

```bash
echo "supersecret" > /run/secrets/db_password
```

Diese Datei wird dann automatisch von `resolveSecret()` geladen:

```php
Config::get()->loadCoreSecrets();
```

Folgende Quellen werden automatisch geprüft (Reihenfolge):

1. Datei über ENV-Variable (`DB_PASSWORD_FILE`)
2. Fallback-Datei (`/run/secrets/db_password`)
3. Direktwert via `getenv('DB_PASSWORD')`
4. Fallback-Parameter (optionaler Funktionsparameter)

---

## Verfügbare Presets

| Methode | Beschreibung |
|---------|-------------|
| `applyDefaults()` | Automatisch je nach Kontext |
| `useCliPreset()` | Für CLI-Calls, Debug-optimiert |
| `useDevelopmentPreset()` | Für Dev-Umgebungen |
| `useProductionPreset()` | Für produktive Systeme mit APP_ROOT |
| `useProductionPresetVHost()` | Für produktive VHost-basierte Setups |

---

## Sicherheitsfeatures

- Keine Secrets im Git oder im ENV-File notwendig
- Secrets nur zur Laufzeit gelesen
- TLS/mTLS Auto-Konfiguration wenn Zertifikate unter `/run/tls/` vorhanden
- `setPhpSettings()` überschreibt bestehende Werte zuverlässig

---

## Beispielhafte Nutzung

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
```

---

## TYPO3-Versionskompatibilität

| TYPO3 Version | Status |
|---------------|--------|
| v11 | Unterstützt |
| v12 | Unterstützt (`pagesection`-Cache automatisch entfernt) |
| v13 | Unterstützt (`imagesizes`-Cache automatisch entfernt) |
| v14 | Unterstützt |

---

## Entwicklung & Qualität

```bash
composer install           # Abhängigkeiten installieren
composer test              # PHPUnit Tests ausführen
composer test:coverage     # Tests mit Coverage-Report
composer phpstan           # PHPStan Level 5 statische Analyse
```

- PHPUnit 9.6 Testsuite mit 35 Tests und >120 Assertions
- PHPStan Level 5 statische Analyse
- Line-Coverage >70% auf `src/Config.php`
- Namespace-basiertes Function-Mocking via `php-mock/php-mock-phpunit`

---

## Ordnerstruktur

```
src/
├── Config.php              ← Hauptklasse (Singleton, Fluent API)
└── ConfigInterface.php     ← Vollständiger Interface-Vertrag
tests/
├── ConfigTestCase.php      ← Basis-Testklasse (Singleton-Reset, $GLOBALS-Isolation)
├── TestableConfig.php      ← Test-Subklasse (Versions-Injection, resolveSecret-Zugang)
├── SingletonTest.php
├── InterfaceCompletenessTest.php
├── PhpSettingsTest.php
├── CacheConfigurationTest.php
├── SecretResolutionTest.php
├── PresetTest.php
└── MtlsConfigurationTest.php
```

---

## Lizenz

MIT

---

## Über Moselwal

Die Moselwal Digitalagentur GmbH steht für sichere, effiziente und automatisierte TYPO3-Infrastrukturen mit hoher Qualität, DevSecOps-Kultur und nachhaltigen Lösungen – speziell für Hidden Champions und Kliniken.

*„Wir transformieren digitale Prozesse – mit Sorgfalt, Struktur und Sicherheit."*

---

## Kontakt

**Moselwal Digitalagentur GmbH**
Monheim am Rhein, Deutschland
