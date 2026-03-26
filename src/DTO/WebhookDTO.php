<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Webhook registration in weclapp.
 *
 * Webhooks allow external systems to receive real-time notifications
 * when weclapp data changes, eliminating the need for polling.
 *
 * Supported event types include:
 *   - article.updated
 *   - salesOrder.created / salesOrder.updated
 *   - party.created / party.updated  (covers customers, contacts, suppliers)
 *
 * @see \miralsoft\weclapp\api\Resource\WebhookResource
 */
final class WebhookDTO extends AbstractDTO
{
    /**
     * @param string      $id            Internal weclapp UUID.
     * @param int         $createdDate   Creation timestamp in epoch milliseconds.
     * @param int         $lastModifiedDate Last modification timestamp in epoch milliseconds.
     * @param bool        $active        Whether the webhook is currently active.
     * @param string      $eventType     The event type that triggers this webhook
     *                                   (e.g. "article.updated", "salesOrder.created").
     * @param string      $callbackUrl   The HTTPS URL that weclapp will POST the event payload to.
     * @param string|null $description   Optional description of the webhook's purpose.
     */
    public function __construct(
        public readonly string  $id,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly bool    $active,
        public readonly string  $eventType,
        public readonly string  $callbackUrl,
        public readonly ?string $description,
    ) {}

    /**
     * Create a WebhookDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:               self::str($data, 'id'),
            createdDate:      self::int($data, 'createdDate'),
            lastModifiedDate: self::int($data, 'lastModifiedDate'),
            active:           self::bool($data, 'active', true),
            eventType:        self::str($data, 'eventType'),
            callbackUrl:      self::str($data, 'callbackUrl'),
            description:      self::strOrNull($data, 'description'),
        );
    }

    /**
     * Returns the creation date as a DateTimeImmutable object.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['createdDate' => $this->createdDate], 'createdDate');
    }

    /**
     * Returns the last modification date as a DateTimeImmutable object.
     */
    public function getLastModifiedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['lastModifiedDate' => $this->lastModifiedDate], 'lastModifiedDate');
    }
}
