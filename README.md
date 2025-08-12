# typo3-config – Zentrale Konfigurationsbasis für TYPO3-Projekte

**Moselwal Digitalagentur GmbH – Konfigurationen, die funktionieren.**

Dieses Paket stellt eine zentrale Klasse `\Moselwal\Config` bereit, mit der TYPO3-Projekte automatisiert, sicher und effizient vorkonfiguriert werden können. Es unterstützt kontextspezifische Presets, sicheres Secret-Management, Logging, Caching, Mailer-Setups und vieles mehr.

---

## ✨ Features

- Automatische Konfiguration für:
    - TYPO3 Caches (Redis, APCu)
    - Mailer (SMTP, Mailpit)
    - Logging (kontextabhängig)
    - Image-Engines (ImageMagick, GraphicsMagick)
    - PHP Settings
- Presets für `Production`, `CLI`, `Development` & `Testing`
- Sichere `resolveSecret()`-Kaskade (ENV, Dateien, Docker Secrets, Fallbacks)
- Fluent Interface – einfach & lesbar
- Logging für fehlende Secrets in Dev & CLI

---

## 🚀 Installation

Mit Composer installieren:

```bash
composer require moselwal/typo3-config


⸻

🛠️ Beispiel: Einbindung in config/config.php

<?php

use Moselwal\Config;

Config::initialize()
    ->loadCoreSecrets()
    ->loadMailSecrets()
    ->applyDefaults();


⸻

🔐 Beispiel: Secrets in /run/secrets/ nutzen

Lege deine Secrets in Dateien ab (z.B. durch Docker Secrets oder Kubernetes Mounts):

echo "supersecret" > /run/secrets/db_password

Diese Datei wird dann automatisch von resolveSecret() geladen:

Config::get()->loadCoreSecrets();

Folgende Quellen werden automatisch geprüft (Reihenfolge):
	1.	Datei über ENV-Variable (KEYVALUE_PASSWORD_FILE)
	2.	Fallback-Datei (/run/secrets/keyvalue_password)
	3.	Direktwert via getenv('KEYVALUE_PASSWORD')
	4.	Harte Fallback-Variable (z.B. .env-Wert via $_ENV[])

⸻

🧩 Verfügbare Presets

Methode	Beschreibung
applyDefaults()	Automatisch je nach Kontext
useCliPreset()	Für CLI-Calls, Debug-optimiert
useDevelopmentPreset()	Für Dev-Umgebungen
useProductionPreset()	Für produktive Systeme mit APP_ROOT
useProductionPresetVHost()	Für produktive VHost-basierte Setups


⸻

🛡️ Sicherheitsfeatures
	•	Keine Secrets im Git oder im ENV-File notwendig
	•	Secrets nur zur Laufzeit gelesen
	•	Logging bei fehlenden Secrets (nur in Dev oder CLI)

⸻

🧪 Beispielhafte Nutzung in Feature-Setup

Config::get()
    ->useGraphicsMagick()
    ->useMailpit()
    ->setPhpSettings([
        'memory_limit' => '512M',
        'max_execution_time' => 120,
    ])
    ->setConfigPathValues('SYS', [
        'defaultScheme' => 'https',
    ]);


⸻

📁 Ordnerstruktur (Beispiel)

typo3conf/
├── config/
│   └── config.php         ← Hier wird Config::initialize() aufgerufen
├── secrets/
│   └── db_password
│   └── encryption_key
│   └── mail_password


⸻

📚 Lizenz

GPL-3.0-or-later

⸻

🏢 Über Moselwal

Die Moselwal Digitalagentur GmbH steht für sichere, effiziente und automatisierte TYPO3-Infrastrukturen mit hoher Qualität, DevSecOps-Kultur und nachhaltigen Lösungen – speziell für Hidden Champions und Kliniken.

„Wir transformieren digitale Prozesse – mit Sorgfalt, Struktur und Sicherheit.“

⸻

💬 Kontakt

Moselwal Digitalagentur GmbH
moselwal.de
Monheim am Rhein, Deutschland
