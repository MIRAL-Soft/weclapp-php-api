<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use miralsoft\weclapp\api\Enum\WebhookEntityName;
use miralsoft\weclapp\api\Enum\WebhookEventAction;

/**
 * Represents an incoming weclapp webhook event payload.
 *
 * Live-confirmed structure (logged delivery from a real tenant, 2026-06-11):
 *
 * ```json
 * {"entityId":"975300","entityName":"contact","type":"UPDATE"}
 * ```
 *
 * weclapp POSTs this JSON body to the subscribed URL. **The requests are NOT
 * signed** — the delivery carried no signature/HMAC header of any kind (only
 * User-Agent "weclapp/<n> (weclapp webhook sender)", Content-Type and proxy
 * headers). Treat webhook payloads as untrusted triggers:
 *
 *   1. Never act on the payload content directly.
 *   2. Re-read the entity through the authenticated API and work with that:
 *      `$client->...()->find($event->entityId)`.
 *   3. Optionally embed a random token in the subscribed URL
 *      (e.g. `https://app.example.com/hooks/weclapp/{random}`) and reject
 *      requests to other paths — the only available spoofing hurdle.
 *
 * @example In your webhook endpoint:
 * ```php
 * $event = WebhookEventDTO::fromJson(file_get_contents('php://input'));
 *
 * if ($event !== null && $event->entityName === WebhookEntityName::SalesOrder->value) {
 *     $order = $client->salesOrders()->find($event->entityId); // authoritative read
 *     // ... process $order
 * }
 * ```
 *
 * @see \miralsoft\weclapp\api\Resource\WebhookResource
 * @see \miralsoft\weclapp\api\Enum\WebhookEventAction
 */
final class WebhookEventDTO extends AbstractDTO
{
    /**
     * @param string $entityId   ID of the affected entity (e.g. "975300").
     * @param string $entityName Entity type, matches the subscription's entityName (e.g. "contact").
     * @param string $type       Action: "CREATE" | "UPDATE" | "DELETE". See WebhookEventAction.
     */
    public function __construct(
        public readonly string $entityId,
        public readonly string $entityName,
        public readonly string $type,
    ) {}

    /**
     * Create a WebhookEventDTO from a decoded webhook payload array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            entityId:   self::str($data, 'entityId'),
            entityName: self::str($data, 'entityName'),
            type:       self::str($data, 'type'),
        );
    }

    /**
     * Parse a raw webhook request body (JSON string) into a WebhookEventDTO.
     *
     * Returns null when the body is not valid JSON or carries no entityId —
     * callers should treat that as "not a weclapp webhook" and respond 400.
     *
     * @example
     * $event = WebhookEventDTO::fromJson(file_get_contents('php://input'));
     * if ($event === null) { http_response_code(400); exit; }
     */
    public static function fromJson(string $rawBody): ?static
    {
        $data = json_decode($rawBody, true);

        if (!is_array($data) || !isset($data['entityId']) || $data['entityId'] === '') {
            return null;
        }

        return static::fromArray($data);
    }

    /**
     * Returns the action as a typed enum, or null for unknown values.
     */
    public function getAction(): ?WebhookEventAction
    {
        return WebhookEventAction::tryFrom($this->type);
    }

    /**
     * Returns the entity name as a typed enum, or null for unknown values.
     */
    public function getEntityName(): ?WebhookEntityName
    {
        return WebhookEntityName::tryFrom($this->entityName);
    }
}
