# CI-Einrichtung (GitHub Actions)

Diese Anleitung beschreibt, wie die CI für `miralsoft/weclapp-php-api` auf GitHub
aktiviert wird. Die Workflow-Dateien liegen bereits im Repository — es ist nur
einmalige Konfiguration auf GitHub nötig.

## Was die CI tut

**`.github/workflows/ci.yml`** — läuft automatisch bei jedem Push und Pull Request:
1. Unit-Tests (`phpunit --testsuite Unit`) auf PHP 8.3 **und** 8.4
2. Statische Analyse (`phpstan analyse`, Level 6)

Schlägt einer der Schritte fehl, wird der Commit/PR rot markiert.
Es werden **keine Credentials** benötigt — die Unit-Tests laufen vollständig
gegen Guzzle-Mocks, ohne Netzwerkzugriff.

**`.github/workflows/update-openapi-spec.yml`** — hält `openapi_v2.json` aktuell:
- Läuft jeden Montag 05:30 UTC (und manuell per Knopfdruck)
- Lädt die aktuelle Spec von `https://{tenant}.weclapp.com/webapp/api/v2/meta/openapi.json`
- Committet die Datei nur, wenn sie sich geändert hat

## Einmalige Einrichtung auf GitHub

### Schritt 1 — Workflows pushen
Die beiden Dateien unter `.github/workflows/` müssen auf GitHub liegen
(sind in diesem Branch enthalten). Nach dem Push erscheint im Repository der
Tab **Actions**.

### Schritt 2 — Secrets für den Spec-Updater anlegen
Nur für den wöchentlichen Spec-Download nötig (die Test-CI braucht keine Secrets):

1. Auf GitHub: **Settings → Secrets and variables → Actions → New repository secret**
2. Zwei Secrets anlegen:
   | Name | Wert |
   |---|---|
   | `WECLAPP_TENANT` | `miralsoft` |
   | `WECLAPP_TOKEN` | API-Token (weclapp → Einstellungen → API) |

> Empfehlung: dafür einen eigenen, **rein lesenden** API-Benutzer in weclapp
> anlegen — der Spec-Download braucht keine Schreibrechte.

### Schritt 3 — Schreibrechte für den Spec-Updater prüfen
Der Updater committet Änderungen zurück ins Repo. Dafür muss unter
**Settings → Actions → General → Workflow permissions** die Option
**"Read and write permissions"** aktiviert sein (oder die im Workflow gesetzte
`permissions: contents: write` greift — das ist bei Standard-Einstellungen der Fall).

### Schritt 4 — Funktionstest
1. **Test-CI:** irgendeinen Commit pushen → Tab **Actions** → Workflow „CI" muss grün werden.
2. **Spec-Updater:** Tab **Actions** → „Update OpenAPI Spec" → **Run workflow** →
   nach ~1 Minute prüfen, ob der Lauf grün ist. Wenn sich die Spec geändert hat,
   erscheint ein Commit `chore: update weclapp OpenAPI spec`.

### Optional — Branch-Schutz
Unter **Settings → Branches → Add branch ruleset** für `main` (und ggf. `WeclappAPIv2`):
- ✅ Require status checks to pass → Check „Unit tests & static analysis" auswählen

Damit kann nichts gemergt werden, was Tests oder PHPStan bricht.

## Lokal dieselben Checks ausführen

```bash
composer test          # Unit-Tests
composer analyse       # PHPStan Level 6
composer spec:update   # OpenAPI-Spec aktualisieren (braucht tests/.env.test)
```

Unter Laragon mit PHP 8.3:
```bash
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe vendor\bin\phpunit --testsuite Unit
```

## Hinweise

- Die Spec vom Tenant-Endpoint enthält nur die für die Lizenz freigeschalteten
  Endpoints (~450 Pfade). Einzelne live funktionierende Endpoints (z. B.
  `/recurringInvoice`) fehlen in der Spec komplett — die Spec ist Referenz,
  nicht vollständige Wahrheit. Kritisches Verhalten immer gegen die Live-API
  verifizieren.
- `composer.lock` ist in diesem Repo bewusst gitignored; die CI installiert
  daher immer die neuesten zu `composer.json` passenden Versionen.
