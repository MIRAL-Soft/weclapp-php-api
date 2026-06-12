# CI-Einrichtung (GitHub Actions)

Diese Anleitung beschreibt, wie die CI für `miralsoft/weclapp-php-api` auf GitHub
aktiviert wird. Die Workflow-Datei liegt bereits im Repository — es ist nur
minimale Konfiguration auf GitHub nötig.

## Bewusste Entscheidung: kein weclapp-Token in der CI

Die CI verwendet **kein weclapp-Token**. Grund: weclapp-Tokens sind nicht auf
„nur lesen" einschränkbar (die Rechte hängen am Benutzer, und jeder zusätzliche
weclapp-Benutzer kostet Lizenz). Ein schreibfähiges Token in einem
GitHub-Secret abzulegen ist das Risiko nicht wert. Sicherheit geht hier vor
Komfort.

Konsequenz:
- Die **Test-CI** läuft vollständig **ohne Token** (alle Unit-Tests sind gegen
  Guzzle-Mocks ausgeführt, kein Netzwerkzugriff).
- Die **OpenAPI-Spec** wird **nicht** automatisch in der Cloud aktualisiert,
  sondern **lokal bei Bedarf** mit `composer spec:update` — dabei bleibt dein
  persönliches Token auf deinem Rechner und verlässt ihn nie.
- Die **Integrationstests** laufen ebenfalls nur lokal (sie brauchen einen echten
  Tenant) und **nie** in der CI.

## Was die CI tut

**`.github/workflows/ci.yml`** — läuft automatisch bei jedem Push und Pull Request:
1. Unit-Tests (`phpunit --testsuite Unit`) auf PHP 8.3 **und** 8.4
2. Statische Analyse (`phpstan analyse`, Level 6)

Schlägt einer der Schritte fehl, wird der Commit/PR rot markiert.
**Keine Credentials, keine Secrets, keine besonderen Berechtigungen nötig** — die
Standard-Leserechte (auch unter restriktiver Org-Policy) genügen.

## Einrichtung auf GitHub

### Schritt 1 — Workflow auf GitHub bringen
Die Datei `.github/workflows/ci.yml` muss auf GitHub liegen (ist in diesem Branch
enthalten). Nach dem Push erscheint im Repository der Tab **Actions** und die CI
läuft ab dem nächsten Push automatisch.

> Hinweis: `ci.yml` nutzt `on: push` und läuft daher auf **jedem** Branch — auch
> bevor `WeclappAPIv2` nach `main` gemergt ist.

### Schritt 2 — Funktionstest
Irgendeinen Commit pushen → Tab **Actions** → Workflow „CI" muss grün werden
(~25 s). Per Klick auf einen Lauf siehst du die einzelnen Schritte (Tests 8.3,
Tests 8.4, PHPStan).

### Optional — Branch-Schutz
Unter **Settings → Branches → Add branch ruleset** für `main` (und ggf. `WeclappAPIv2`):
- ✅ Require status checks to pass → Check „Unit tests & static analysis" auswählen

Damit kann nichts gemergt werden, was Tests oder PHPStan bricht.

## OpenAPI-Spec lokal aktualisieren

Da die Spec nicht automatisch aktualisiert wird, bei Bedarf manuell:

```bash
composer spec:update
```

Lädt die aktuelle Spec von
`https://{tenant}.weclapp.com/webapp/api/v2/meta/openapi.json` (Credentials aus
`tests/.env.test` oder den Umgebungsvariablen), validiert sie und überschreibt
`openapi_v2.json`. Anschließend die Änderung normal committen, falls sich etwas
geändert hat. Empfehlung: ab und zu laufen lassen (z. B. vor größeren Änderungen
an Resources/DTOs), damit die lokale Referenz nicht veraltet.

## Lokal dieselben Checks wie die CI ausführen

```bash
composer test          # Unit-Tests
composer analyse       # PHPStan Level 6
```

Unter Laragon mit PHP 8.3:
```bash
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe vendor\bin\phpunit --testsuite Unit
```

## Integrationstests (nur lokal, optional)

Brauchen einen echten Tenant und ein Token in `tests/.env.test` (gitignored).
Sie laufen **nie** in der CI. Write-Tests sind zusätzlich hinter
`WECLAPP_ALLOW_WRITES=true` gated und räumen via try/finally hinter sich auf.

```bash
php vendor/bin/phpunit --testsuite Integration   # read-only
WECLAPP_ALLOW_WRITES=true php vendor/bin/phpunit --testsuite Write
```

## Hinweise

- Die Spec vom Tenant-Endpoint enthält nur die für die Lizenz freigeschalteten
  Endpoints (~450 Pfade). Einzelne live funktionierende Endpoints (z. B.
  `/recurringInvoice`) fehlen in der Spec komplett — die Spec ist Referenz,
  nicht vollständige Wahrheit. Kritisches Verhalten immer gegen die Live-API
  verifizieren.
- `composer.lock` ist in diesem Repo bewusst gitignored; die CI installiert
  daher immer die neuesten zu `composer.json` passenden Versionen.
