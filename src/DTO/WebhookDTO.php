<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Webhook subscription in the weclapp API.
 *
 * Maps to the webhook schema. Webhooks deliver real-time event notifications
 * to an external URL when weclapp entities are created, updated, or deleted.
 * Which events fire is controlled by the three boolean flags atCreate, atUpdate,
 * atDelete combined with the entityName field.
 *
 * A webhook is considered active when deactivatedDate is null.
 * weclapp may deactivate a webhook automatically after repeated delivery failures.
 *
 * @see \miralsoft\weclapp\api\Resource\WebhookResource
 */
final class WebhookDTO extends AbstractDTO
{
    /**
     * @param string      $id               Internal weclapp UUID.
     * @param string      $version          Record version (optimistic locking, readOnly).
     * @param int         $createdDate      Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate Last modification timestamp in epoch milliseconds (readOnly).
     * @param bool        $atCreate         Whether this webhook fires on entity creation.
     * @param bool        $atDelete         Whether this webhook fires on entity deletion.
     * @param bool        $atUpdate         Whether this webhook fires on entity update.
     * @param int|null    $deactivatedDate  Timestamp (epoch ms) when the webhook was deactivated, null if active.
     * @param string      $entityName       The weclapp entity type to watch (e.g. "party", "salesOrder").
     * @param string|null $errorMessage     Last delivery error message reported by weclapp, if any.
     * @param string      $requestMethod    HTTP method used for delivery: "GET" or "POST".
     * @param string      $url              The URL that receives the webhook payload (max 1000 chars).
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly bool    $atCreate,
        public readonly bool    $atDelete,
        public readonly bool    $atUpdate,
        public readonly ?int    $deactivatedDate,
        public readonly string  $entityName,
        public readonly ?string $errorMessage,
        public readonly string  $requestMethod,
        public readonly string  $url,
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
            version:          self::str($data, 'version'),
            createdDate:      self::int($data, 'createdDate'),
            lastModifiedDate: self::int($data, 'lastModifiedDate'),
            atCreate:         self::bool($data, 'atCreate'),
            atDelete:         self::bool($data, 'atDelete'),
            atUpdate:         self::bool($data, 'atUpdate'),
            deactivatedDate:  self::intOrNull($data, 'deactivatedDate'),
            entityName:       self::str($data, 'entityName'),
            errorMessage:     self::strOrNull($data, 'errorMessage'),
            requestMethod:    self::str($data, 'requestMethod'),
            url:              self::str($data, 'url'),
        );
    }

    /**
     * Returns true when the webhook is currently active (deactivatedDate is null).
     */
    public function isActive(): bool
    {
        return $this->deactivatedDate === null;
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

    /**
     * Returns the deactivation timestamp as a DateTimeImmutable, or null if the webhook is still active.
     */
    public function getDeactivatedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['deactivatedDate' => $this->deactivatedDate], 'deactivatedDate');
    }
}
