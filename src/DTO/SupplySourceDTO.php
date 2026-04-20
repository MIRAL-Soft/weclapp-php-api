<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a supply source (procurement source) for an Article.
 *
 * Maps to the supplySource schema in the weclapp API.
 * Instances are embedded inside ArticleDTO::$supplySources.
 *
 * API key name on the parent article: supplySources
 *
 * @see \miralsoft\weclapp\api\DTO\ArticleDTO
 */
final class SupplySourceDTO extends AbstractDTO
{
    /**
     * @param string  $id                    Internal weclapp UUID (readOnly).
     * @param string  $version               Optimistic locking version string (readOnly).
     * @param int     $createdDate           Creation timestamp in epoch milliseconds (readOnly).
     * @param int     $lastModifiedDate      Last modification timestamp in epoch milliseconds (readOnly).
     * @param string  $articleSupplySourceId ID referencing the actual supply source record.
     * @param int     $positionNumber        Display/priority position of this supply source.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $version,
        public readonly int    $createdDate,
        public readonly int    $lastModifiedDate,
        public readonly string $articleSupplySourceId,
        public readonly int    $positionNumber,
    ) {}

    /**
     * Create a SupplySourceDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                    self::str($data, 'id'),
            version:               self::str($data, 'version'),
            createdDate:           self::int($data, 'createdDate'),
            lastModifiedDate:      self::int($data, 'lastModifiedDate'),
            articleSupplySourceId: self::str($data, 'articleSupplySourceId'),
            positionNumber:        self::int($data, 'positionNumber'),
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
