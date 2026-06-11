# Prompt für den Konsumenten: Webhook-Signatur-Prüfung

> ## ✅ ERLEDIGT — Ergebnis vom 11.06.2026
>
> Der Konsument hat eine echte Zustellung geloggt:
>
> ```json
> {
>     "method": "POST",
>     "ip": "3.73.185.97",
>     "headers": {
>         "User-Agent": "weclapp/22 (weclapp webhook sender)",
>         "Content-Type": "application/json",
>         "Content-Length": "60"
>     },
>     "rawBody": "{\"entityId\":\"975300\",\"entityName\":\"contact\",\"type\":\"UPDATE\"}"
> }
> ```
>
> **Befund:** KEIN Signatur-/HMAC-Header vorhanden. weclapp signiert Webhooks
> nicht. Konsequenz umgesetzt: `WebhookValidator` aus der Library entfernt,
> Payload-Format als bestätigt dokumentiert (Feld heißt `type`, nicht
> `eventType`; Werte uppercase), neues `WebhookEventDTO` zum Parsen ergänzt.
> Sicherheitsmodell: Webhooks sind reine Trigger — Daten immer per
> authentifiziertem API-Read holen.

---

> *Ursprünglicher Prompt (archiviert):* Diesen Prompt an das Projekt geben, das
> die weclapp-Webhooks empfängt (z. B. den weclapp→Docbee-Exporter). Ziel: ein
> für alle Mal klären, ob weclapp Webhook-Requests signiert.

---

## Aufgabe: Verifiziere per Logging, ob weclapp Webhook-Requests signiert

### Kontext

Die Library `miralsoft/weclapp-api` enthält zwei sich widersprechende Aussagen:

1. `src/Util/WebhookValidator.php` behauptet: weclapp signiert Webhook-Requests
   mit HMAC-SHA256 über den Raw-Body und sendet die Signatur im Header
   `X-Weclapp-Signature`.
2. Die live-verifizierte Recherche in `src/Resource/WebhookResource.php`
   (Stand 04.05.2026) sagt: das weclapp-Webhook-Schema hat **kein** Secret- und
   kein Signatur-Feld; es findet keine Signierung statt.

Nur eine der beiden kann stimmen. Du empfängst echte weclapp-Webhooks und
kannst das empirisch klären.

### Was du tun sollst

1. **Temporäres Diagnose-Logging am Webhook-Empfangsendpunkt einbauen.**
   An der Stelle, an der die weclapp-Webhooks ankommen (Controller/Route),
   VOR jeder Verarbeitung Folgendes in eine eigene Logdatei
   (z. B. `var/log/weclapp-webhook-diagnose.log`) schreiben:

   - Zeitstempel und HTTP-Methode
   - **ALLE Request-Header** (Name + Wert), mit einer Ausnahme:
     Werte von Headern, deren Name `authorization`, `token`, `secret` oder
     `cookie` enthält, nur als `***vorhanden, Länge N***` loggen — niemals im
     Klartext.
   - Die ersten 2000 Zeichen des Raw-Bodys (`php://input` / Raw-Request-Body —
     wichtig: den unveränderten Body, nicht das geparste JSON)
   - Die Quell-IP des Requests

   Beispiel (framework-neutral):
   ```php
   $headers = [];
   foreach (getallheaders() as $name => $value) {
       $headers[$name] = preg_match('/authorization|token|secret|cookie/i', $name)
           ? '***vorhanden, Länge ' . strlen($value) . '***'
           : $value;
   }
   file_put_contents(
       __DIR__ . '/weclapp-webhook-diagnose.log',
       json_encode([
           'time'    => date('c'),
           'method'  => $_SERVER['REQUEST_METHOD'],
           'ip'      => $_SERVER['REMOTE_ADDR'] ?? '?',
           'headers' => $headers,
           'rawBody' => substr(file_get_contents('php://input'), 0, 2000),
       ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n---\n",
       FILE_APPEND
   );
   ```

2. **Mindestens 3 echte Webhook-Zustellungen provozieren.**
   In weclapp eine überwachte Entität ändern (z. B. einen Auftrag öffnen,
   ein Feld ändern, speichern) — für jede Änderung feuert der Webhook.
   Falls kein Webhook aktiv ist: per Library
   `$client->webhooks()->ensureSubscription(...)` einen auf die Empfangs-URL
   registrieren.

3. **Auswerten und zurückmelden.** Beantworte anhand des Logs:

   | Frage | Antwort |
   |---|---|
   | Ist ein Header `X-Weclapp-Signature` vorhanden? | ja/nein |
   | Gibt es IRGENDEINEN signatur-/HMAC-artigen Header? (`X-Signature`, `X-Hub-Signature`, `X-Webhook-Signature`, `Digest`, …) | ja/nein, welcher |
   | Vollständige Liste aller Header-NAMEN einer Zustellung | Liste |
   | HTTP-Methode der Zustellung | GET/POST |
   | Struktur des Bodys (Top-Level-Keys, z. B. `entityId`, `entityName`, `eventType`) | Liste |
   | Quell-IP(s) der Zustellungen | Liste |

   Bitte den relevanten Log-Auszug (eine vollständige Zustellung, Header
   maskiert wie oben) mitliefern.

4. **Aufräumen.** Nach der Auswertung das Diagnose-Logging wieder entfernen
   (oder hinter ein Config-Flag legen) und die Logdatei löschen — sie enthält
   Geschäftsdaten aus den Webhook-Bodies.

### Warum das wichtig ist

- Falls **kein** Signatur-Header existiert: `WebhookValidator` wird aus der
  Library entfernt/umgewidmet, und die Doku stellt klar, dass weclapp-Webhooks
  nur als **Trigger** taugen (anschließende Daten immer per authentifiziertem
  API-Read holen, nie dem Webhook-Body vertrauen).
- Falls **doch** ein Signatur-Header existiert: der `WebhookValidator` wird
  zum Pflicht-Baustein, und wir dokumentieren, wo das Secret in weclapp
  konfiguriert wird.

### Sicherheitsregeln für diese Aufgabe

- Keine Tokens/Secrets im Klartext loggen (Maskierung wie oben).
- Die Logdatei nicht committen und nach Abschluss löschen.
- Keine Änderungen an der Webhook-Verarbeitung selbst — nur lesendes Logging davor.
