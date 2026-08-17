# Projekt-Status & Wiederaufnahme (Handover)

> Diese Datei ist der Anker für die Wiederaufnahme der Arbeit — sie liegt bewusst
> **im Repo** (auf GitHub), damit sie über Account-/Rechnerwechsel hinweg erhalten
> bleibt. Stand: **2026-08-17**.

## Wo stehen wir

- **Branch:** `WeclappAPIv2` (Default-Branch des Repos ist `main`).
- **Zustand:** Alles implementiert, committet und **gepusht**. Arbeitsbaum sauber.
- **Qualität:** 376 Unit-Tests grün, PHPStan **Level 6** sauber (0 Fehler).
- **Letzter Commit:** `a6a7cbe` — feat: web UI deep-link builder.
- Die Library ist funktional vollständig und dokumentiert (README + CHANGELOG +
  Docblocks). An der Library selbst ist **nichts offen**.

## Was zuletzt gebaut wurde (Kurzüberblick der Session)

Consumer-Features: `findByCustomerNumber`, `cursor()`, `patch()`, `findRaw()` public,
`QuantityUnitResource`, `CustomAttributeDefinitionResource` (voll inkl. ensure/order),
`SalesInvoiceResource::findBySalesOrder()`, `RecurringInvoiceResource` (read-only),
ms-präzises `QueryBuilder::toEpochMs()`, Web-UI-Deeplink-Builder (`webUrl()`).

Härtung: ID-URL-Encoding überall (`AbstractResource::idPath()`), Tenant/Version-
Validierung in `WeclappConfig`, `findOpen()`-Fix (paymentStatus statt nicht-existentem
openAmount), 50er-Kappung in `findBy*` behoben, PHPStan Level 6 eingeführt.

Aufräumen: Legacy-v1-Klassen komplett entfernt (**Breaking**), `WebhookValidator`
entfernt (weclapp signiert Webhooks NICHT — live verifiziert), `WebhookEventDTO`
hinzugefügt, token-freie CI (`.github/workflows/ci.yml`).

## Offene Punkte (NICHT Code dieser Library)

### 1. Release: `WeclappAPIv2` → `main` mergen
- Enthält einen **Breaking Change** (Legacy-v1 entfernt) → nächste **MAJOR**-Version taggen.
- ⚠️ **Squash-Merge verwenden** (nicht normaler Merge-Commit): Die Feature-Branch-Commits
  tragen teils `Co-Authored-By`-Trailer aus der Frühphase; per Squash landen sie **nicht**
  in `main`. (Ab sofort gilt ohnehin: keine Co-Author-Trailer mehr, siehe unten.)
- Erst nach dem Merge liegen die CI-Workflows auf `main` und werden aktiv.

### 2. Konsument „docbee-Exporter" (ANDERES Repo)
- Temporäres Webhook-Diagnose-Logging am Empfangsendpunkt wieder **entfernen**
  (loggt Geschäftsdaten) — war nur zur Klärung der Signatur-Frage.
- Optional: auf `WebhookEventDTO::fromJson()` umstellen.
- Prüfen, ob im Exporter irgendwo auf `eventType` statt **`type`** geprüft wird
  (das echte weclapp-Webhook-Feld heißt `type`, Werte uppercase CREATE/UPDATE/DELETE).

### 3. Library „docbee-api" (ANDERES Repo)
- Vereinbarte **Option A** steht noch aus: `findFields` auf die List-Methoden
  (`list`/`listAll`/`findModifiedSince`/`findCreatedSince`) ausdehnen, damit list dieselben
  Felder liefert wie `find()`. (Dieses Problem existiert NUR in der docbee-api, nicht hier —
  weclapp liefert für find und list dasselbe Feld-Set.)

## Konventionen & Arbeitsumgebung (für die Wiederaufnahme)

- **KEIN `Co-Authored-By`-Trailer** in Commits/PRs (harte Regel).
- **PHP lokal:** Standard-System-PHP ist 8.2; die Library verlangt `^8.3`. Für Tests/Composer
  die **Laragon-PHP 8.3** nutzen:
  `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe` (8.4.12 ist ebenfalls vorhanden).
- **Befehle:**
  - `composer test` → Unit-Tests (`phpunit --testsuite Unit`)
  - `composer analyse` → PHPStan Level 6
  - `composer spec:update` → `openapi_v2.json` lokal von der Live-API aktualisieren
    (Credentials aus `tests/.env.test`)
- **Integrationstests:** nur lokal, Token in `tests/.env.test` (gitignored). Write-Tests
  sind hinter `WECLAPP_ALLOW_WRITES=true` gated. Laufen **nie** in der CI.
- **OpenAPI-Spec:** `openapi_v2.json` ist die Referenz — enthält nur lizenzierte Endpoints
  (~450), einzelne Live-Endpoints (z. B. `/recurringInvoice`) fehlen. Kritisches immer live
  verifizieren.
- **Web-Deeplinks:** neue Entitäten nur nach Live-Verifikation der echten Browser-URL in die
  Mapping-Tabelle in `src/Util/WebUrlBuilder.php` aufnehmen — nie raten.

## GitHub-Kontext

- Org **MIRAL-Soft** erzwingt read-only Workflow-Permissions (Repo-Einstellung ausgegraut).
  Für die token-freie Test-CI egal. Ein Cloud-Spec-Updater wurde bewusst NICHT eingebaut
  (kein weclapp-Token in GitHub-Secrets — Sicherheit vor Komfort). Spec-Update erfolgt lokal.
