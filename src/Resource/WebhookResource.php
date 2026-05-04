<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use InvalidArgumentException;
use miralsoft\weclapp\api\DTO\WebhookDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;

/**
 * Resource class for weclapp Webhook management.
 *
 * Allows registering, listing, updating and deleting webhook subscriptions.
 * Webhooks enable real-time event-driven integration without polling.
 *
 * Each webhook watches one entity type (entityName) and fires on any combination
 * of create / update / delete events as configured by the three boolean flags.
 *
 * The callback URL must be publicly reachable. weclapp sends a request using
 * the configured requestMethod ("GET" or "POST", default "POST") with the event
 * payload as JSON in the body.
 *
 * weclapp may automatically deactivate a webhook after repeated delivery failures;
 * check WebhookDTO::isActive() and WebhookDTO::$errorMessage for status.
 *
 * ============================================================================
 * WEBHOOK API RESEARCH — verified 2026-05-04
 * ============================================================================
 *
 * Endpoint:  /api/v2/webhook
 * HTTP methods:
 *   GET    /api/v2/webhook              — list all subscriptions (paginated)
 *   GET    /api/v2/webhook/count        — count subscriptions
 *   GET    /api/v2/webhook/id/{id}      — find one by UUID
 *   POST   /api/v2/webhook              — create a new subscription
 *   PUT    /api/v2/webhook/id/{id}      — update an existing subscription
 *   DELETE /api/v2/webhook/id/{id}      — permanently delete a subscription
 *
 * Schema (12 fields, user-confirmed on 2026-05-04):
 *   id, version, createdDate, lastModifiedDate — standard identity fields (readOnly)
 *   atCreate, atUpdate, atDelete              — boolean event flags (writable)
 *   deactivatedDate                           — epoch ms timestamp; null = active (writable)
 *   entityName                                — string, max 255 (writable)
 *   errorMessage                              — string, max 255; last delivery error (readOnly)
 *   requestMethod                             — enum: "GET" | "POST" (writable)
 *   url                                       — string, max 1000 (writable)
 *
 * ⚠️  PUT full-payload requirement (verified 2026-05-04):
 *   The webhook PUT endpoint requires ALL fields including the normally read-only
 *   identity fields (id, createdDate, lastModifiedDate). Sending a partial payload
 *   triggers a 400 validation error: "property createdDate is read-only". The fields
 *   must be passed with their CURRENT values from the GET response. Only the fields
 *   you actually want to change should differ. This is unusual for REST APIs and was
 *   confirmed by live testing against the miralsoft tenant.
 *
 * entityName values:
 *   117 values confirmed from the weclapp admin UI on 2026-05-04.
 *   See WebhookEntityName enum for the complete list with categories and @beta tags.
 *   Notable findings:
 *     - `customer`, `contact` and `party` are DISTINCT entityNames — all three exist.
 *     - There is NO `salesOrderItem` entityName. Order item changes are delivered as
 *       `salesOrder` update events containing the full updated order with all items.
 *     - `webhook` itself is a valid entityName (meta-subscriptions).
 *
 * Server-side filtering:
 *   The /webhook endpoint does not appear to support query-parameter filtering by
 *   url or entityName. Methods in this class that filter by these fields therefore
 *   call listAll() and filter client-side. Webhook counts are typically very low
 *   (< 50 per tenant) so one extra GET request per operation is acceptable.
 *
 * Incoming webhook payload (UNCONFIRMED — verify by logging live traffic):
 *   weclapp is expected to POST a JSON body containing at minimum:
 *     - entityId   (string) — UUID of the affected entity
 *     - entityName (string) — same value as the subscription's entityName
 *     - eventType  (string) — expected to be "created" | "updated" | "deleted"
 *   Additional fields (tenantName, timestamp, etc.) may be present. Log incoming
 *   requests in the consumer application to confirm the exact structure.
 *
 * Deactivation:
 *   weclapp sets deactivatedDate automatically after repeated delivery failures.
 *   Manual deactivation via PUT with a deactivatedDate timestamp is expected to
 *   work (the field has no readOnly marker in the schema). Reactivation via PUT
 *   with deactivatedDate: null has NOT been verified against the live API — if
 *   rejected, prompt the user to re-activate manually in the weclapp admin UI.
 *
 * Security:
 *   The weclapp webhook schema has no secret or signature field. No request signing
 *   is performed or supported. Do not rely on weclapp webhooks as a security
 *   boundary — use them only as a trigger for a subsequent authenticated data sync.
 *
 * Limits:
 *   No documented limit on webhook subscriptions per tenant or delivery rate limit
 *   has been confirmed. Treat webhook delivery as best-effort.
 *
 * ============================================================================
 *
 * @example
 * // Idempotent setup — safe to call on every application start
 * $webhook = $client->webhooks()->ensureSubscription(
 *     entityName: WebhookEntityName::SalesOrder->value,
 *     url:        'https://my-app.example.com/webhooks/weclapp',
 *     atCreate:   true,
 *     atUpdate:   true,
 * );
 *
 * // Status check
 * $all = $client->webhooks()->findByUrl('https://my-app.example.com/webhooks/weclapp');
 * foreach ($all as $hook) {
 *     echo $hook->entityName . ': ' . ($hook->isActive() ? 'active' : 'inactive') . PHP_EOL;
 * }
 */
class WebhookResource extends AbstractResource
{
    protected string $endpoint = 'webhook';
    protected string $dtoClass = WebhookDTO::class;

    /**
     * Register a new webhook subscription.
     *
     * At least one of $atCreate, $atUpdate, $atDelete must be true.
     *
     * @param string $entityName    The weclapp entity type to watch (e.g. "party", "salesOrder").
     *                              See WebhookEntityName for the full verified list of values.
     * @param string $url           The URL to deliver events to (max 1000 chars).
     * @param bool   $atCreate      Fire when an entity of this type is created.
     * @param bool   $atUpdate      Fire when an entity of this type is updated.
     * @param bool   $atDelete      Fire when an entity of this type is deleted.
     * @param string $requestMethod HTTP method for delivery: "GET" or "POST" (default "POST").
     *
     * @throws InvalidArgumentException If no event flag is enabled or requestMethod is invalid.
     * @throws WeclappApiException
     */
    public function register(
        string $entityName,
        string $url,
        bool   $atCreate       = false,
        bool   $atUpdate       = false,
        bool   $atDelete       = false,
        string $requestMethod  = 'POST',
    ): WebhookDTO {
        if (!$atCreate && !$atUpdate && !$atDelete) {
            throw new InvalidArgumentException(
                'At least one of $atCreate, $atUpdate, $atDelete must be true when registering a webhook.'
            );
        }

        if (!in_array($requestMethod, ['GET', 'POST'], true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid requestMethod "%s". Allowed values: "GET", "POST".', $requestMethod)
            );
        }

        return $this->create([
            'entityName'    => $entityName,
            'url'           => $url,
            'atCreate'      => $atCreate,
            'atUpdate'      => $atUpdate,
            'atDelete'      => $atDelete,
            'requestMethod' => $requestMethod,
        ]);
    }

    /**
     * Idempotently ensure a webhook subscription exists for the given entity and events.
     *
     * This method is safe to call on every application start or setup run. It avoids
     * creating duplicate subscriptions and only performs an API write when necessary:
     *
     *   1. Fetches all existing webhooks via listAll() and filters client-side.
     *   2. If no webhook with matching entityName + url exists → creates one (POST).
     *   3. If a match is found and all requested flags are already enabled
     *      and requestMethod matches → returns the existing DTO unchanged (no write).
     *   4. If a match exists but a requested flag is not yet enabled, or requestMethod
     *      differs → updates the webhook (PUT) with flags merged additively:
     *      existing true flags are preserved, new ones are added. This prevents
     *      one caller from accidentally disabling flags set by another caller.
     *
     * Note: flag merging is additive only. There is no way to turn a flag off via
     * this method — use update() directly if you need to disable an event type.
     *
     * Parameter names and order are intentionally identical to register() so that
     * both methods can be called interchangeably.
     *
     * @param string $entityName    Entity type to watch. See WebhookEntityName enum.
     * @param string $url           Delivery URL (max 1000 chars; must not be empty).
     * @param bool   $atCreate      Ensure the subscription fires on entity creation.
     * @param bool   $atUpdate      Ensure the subscription fires on entity update.
     * @param bool   $atDelete      Ensure the subscription fires on entity deletion.
     * @param string $requestMethod HTTP delivery method: "GET" or "POST" (default "POST").
     * @return WebhookDTO           The existing (unchanged) or newly created/updated DTO.
     *
     * @throws InvalidArgumentException If $url is empty, no event flag is set, or
     *                                  requestMethod is not "GET" or "POST".
     * @throws WeclappApiException
     */
    public function ensureSubscription(
        string $entityName,
        string $url,
        bool   $atCreate       = false,
        bool   $atUpdate       = false,
        bool   $atDelete       = false,
        string $requestMethod  = 'POST',
    ): WebhookDTO {
        if (trim($url) === '') {
            throw new InvalidArgumentException('The $url parameter must not be empty.');
        }

        if (!$atCreate && !$atUpdate && !$atDelete) {
            throw new InvalidArgumentException(
                'At least one of $atCreate, $atUpdate, $atDelete must be true in ensureSubscription().'
            );
        }

        if (!in_array($requestMethod, ['GET', 'POST'], true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid requestMethod "%s". Allowed values: "GET", "POST".', $requestMethod)
            );
        }

        // Load all webhooks client-side (server-side filtering by url/entityName is
        // not supported by the /webhook endpoint). Tenant webhook counts are typically
        // low (< 50), so the extra GET is acceptable.
        /** @var list<WebhookDTO> $all */
        $all = $this->listAll();

        $match = null;
        foreach ($all as $webhook) {
            if ($webhook->entityName === $entityName && $webhook->url === $url) {
                $match = $webhook;
                break;
            }
        }

        // No existing subscription → create a new one
        if ($match === null) {
            /** @var WebhookDTO */
            return $this->create([
                'entityName'    => $entityName,
                'url'           => $url,
                'atCreate'      => $atCreate,
                'atUpdate'      => $atUpdate,
                'atDelete'      => $atDelete,
                'requestMethod' => $requestMethod,
            ]);
        }

        // Determine whether an update is needed:
        // - A requested flag is not yet enabled on the existing webhook, OR
        // - The requestMethod differs.
        $needsUpdate = ($atCreate  && !$match->atCreate)
            || ($atUpdate  && !$match->atUpdate)
            || ($atDelete  && !$match->atDelete)
            || ($match->requestMethod !== $requestMethod);

        if (!$needsUpdate) {
            return $match;
        }

        // Merge flags additively: existing true flags stay true, new ones are added.
        // weclapp's webhook PUT endpoint requires the full record to be sent back
        // (including the read-only identity fields id, createdDate, lastModifiedDate).
        // Sending a partial payload triggers "property X is read-only" validation errors.
        /** @var WebhookDTO */
        return $this->update($match->id, [
            'id'              => $match->id,
            'version'         => $match->version,
            'createdDate'     => $match->createdDate,
            'lastModifiedDate' => $match->lastModifiedDate,
            'entityName'      => $match->entityName,
            'url'             => $match->url,
            'atCreate'        => $match->atCreate || $atCreate,
            'atUpdate'        => $match->atUpdate || $atUpdate,
            'atDelete'        => $match->atDelete || $atDelete,
            'requestMethod'   => $requestMethod,
            'deactivatedDate' => $match->deactivatedDate,
        ]);
    }

    /**
     * Return all webhooks whose URL exactly matches the given value.
     *
     * Useful for setup status pages ("which subscriptions point to my server?").
     * Filtering is performed client-side after listAll() because the /webhook endpoint
     * does not support server-side URL filtering.
     *
     * Note: comparison is exact (case-sensitive, no trailing-slash normalisation).
     * A stored URL of "https://example.com/hook/" will NOT match "https://example.com/hook".
     *
     * @return list<WebhookDTO>
     *
     * @throws WeclappApiException
     */
    public function findByUrl(string $url): array
    {
        /** @var list<WebhookDTO> $all */
        $all = $this->listAll();

        return array_values(array_filter($all, static fn (WebhookDTO $w): bool => $w->url === $url));
    }

    /**
     * Return all webhooks watching the given entity type.
     *
     * Filtering is performed client-side after listAll() because the /webhook endpoint
     * does not support server-side entityName filtering.
     *
     * @param string $entityName The entity name to filter by (e.g. "salesOrder", "party").
     *                           See WebhookEntityName for the full verified list of values.
     * @return list<WebhookDTO>
     *
     * @throws WeclappApiException
     */
    public function findByEntityName(string $entityName): array
    {
        /** @var list<WebhookDTO> $all */
        $all = $this->listAll();

        return array_values(
            array_filter($all, static fn (WebhookDTO $w): bool => $w->entityName === $entityName)
        );
    }

    /**
     * Deactivate a webhook by setting its deactivatedDate to the current time.
     *
     * This is a soft deactivation — the subscription record is preserved and can be
     * re-activated later. weclapp will stop delivering events for this webhook while
     * deactivatedDate is set.
     *
     * If the webhook is already inactive (deactivatedDate is already set), the
     * existing DTO is returned immediately without making an API write call.
     *
     * @param string $id The weclapp UUID of the webhook to deactivate.
     * @return WebhookDTO The updated (or unchanged if already inactive) DTO.
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException If the webhook does not exist.
     * @throws WeclappApiException
     */
    public function deactivate(string $id): WebhookDTO
    {
        $webhook = $this->find($id);

        if (!$webhook->isActive()) {
            return $webhook; // already deactivated — no write needed
        }

        // weclapp's webhook PUT requires the full record back, including the read-only
        // identity fields (id, createdDate, lastModifiedDate). Partial payloads trigger
        // "property X is read-only" validation errors.
        /** @var WebhookDTO */
        return $this->update($id, [
            'id'               => $webhook->id,
            'version'          => $webhook->version,
            'createdDate'      => $webhook->createdDate,
            'lastModifiedDate' => $webhook->lastModifiedDate,
            'entityName'       => $webhook->entityName,
            'url'              => $webhook->url,
            'atCreate'         => $webhook->atCreate,
            'atUpdate'         => $webhook->atUpdate,
            'atDelete'         => $webhook->atDelete,
            'requestMethod'    => $webhook->requestMethod,
            'deactivatedDate'  => (int) (microtime(true) * 1000),
        ]);
    }

    /**
     * Reactivate a previously deactivated webhook by clearing its deactivatedDate.
     *
     * If the webhook is already active (deactivatedDate is null), the existing DTO
     * is returned immediately without making an API write call.
     *
     * @note The behaviour of PUT with deactivatedDate: null has NOT been verified
     *       against the live weclapp API. The field has no readOnly marker in the
     *       schema, so clearing it via PUT is expected to work. If weclapp rejects
     *       the call, catch the resulting WeclappApiException in the consumer and
     *       prompt the user to re-activate the webhook manually in the weclapp admin UI
     *       under Global Settings → Integrations → Webhooks.
     *
     * @param string $id The weclapp UUID of the webhook to reactivate.
     * @return WebhookDTO The updated (or unchanged if already active) DTO.
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException If the webhook does not exist.
     * @throws WeclappApiException
     */
    public function reactivate(string $id): WebhookDTO
    {
        $webhook = $this->find($id);

        if ($webhook->isActive()) {
            return $webhook; // already active — no write needed
        }

        // weclapp's webhook PUT requires the full record back, including the read-only
        // identity fields (id, createdDate, lastModifiedDate). Partial payloads trigger
        // "property X is read-only" validation errors.
        /** @var WebhookDTO */
        return $this->update($id, [
            'id'               => $webhook->id,
            'version'          => $webhook->version,
            'createdDate'      => $webhook->createdDate,
            'lastModifiedDate' => $webhook->lastModifiedDate,
            'entityName'       => $webhook->entityName,
            'url'              => $webhook->url,
            'atCreate'         => $webhook->atCreate,
            'atUpdate'         => $webhook->atUpdate,
            'atDelete'         => $webhook->atDelete,
            'requestMethod'    => $webhook->requestMethod,
            'deactivatedDate'  => null,
        ]);
    }

    /**
     * Permanently delete a webhook subscription.
     *
     * This action cannot be undone. Use deactivate() instead if you want to
     * temporarily pause event delivery without losing the subscription.
     *
     * @param string $id The weclapp UUID of the webhook to delete.
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException If the webhook does not exist.
     * @throws WeclappApiException
     */
    public function delete(string $id): void
    {
        parent::delete($id);
    }

    /**
     * Retrieve all registered webhook subscriptions.
     *
     * @return list<WebhookDTO>
     *
     * @throws WeclappApiException
     */
    public function all(): array
    {
        /** @var list<WebhookDTO> */
        return $this->listAll();
    }

    /**
     * {@inheritdoc}
     *
     * @return WebhookDTO
     */
    public function find(string $id): WebhookDTO
    {
        /** @var WebhookDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return WebhookDTO
     */
    public function create(array $data): WebhookDTO
    {
        /** @var WebhookDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return WebhookDTO
     */
    public function update(string $id, array $data): WebhookDTO
    {
        /** @var WebhookDTO */
        return parent::update($id, $data);
    }
}
