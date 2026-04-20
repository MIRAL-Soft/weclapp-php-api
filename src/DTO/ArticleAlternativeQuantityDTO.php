<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a warehouse-specific alternative quantity configuration for an Article.
 *
 * Maps to the articleAlternativeQuantity schema in the weclapp API.
 * Instances are embedded inside ArticleDTO::$articleAlternativeQuantities.
 *
 * API key name on the parent article: articleAlternativeQuantities
 *
 * @see \miralsoft\weclapp\api\DTO\ArticleDTO
 */
final class ArticleAlternativeQuantityDTO extends AbstractDTO
{
    /**
     * @param string       $id                   Internal weclapp UUID (readOnly).
     * @param string       $version              Optimistic locking version string (readOnly).
     * @param int          $createdDate          Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate     Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $warehouseId          ID of the warehouse this configuration applies to.
     * @param string|null  $minimumOrderQuantity Minimum order quantity as a decimal string.
     * @param string|null  $minimumStockQuantity Minimum stock level as a decimal string.
     * @param string|null  $targetStockQuantity  Target (ideal) stock level as a decimal string.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $warehouseId,
        public readonly ?string $minimumOrderQuantity,
        public readonly ?string $minimumStockQuantity,
        public readonly ?string $targetStockQuantity,
    ) {}

    /**
     * Create an ArticleAlternativeQuantityDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                   self::str($data, 'id'),
            version:              self::str($data, 'version'),
            createdDate:          self::int($data, 'createdDate'),
            lastModifiedDate:     self::int($data, 'lastModifiedDate'),
            warehouseId:          self::strOrNull($data, 'warehouseId'),
            minimumOrderQuantity: self::strOrNull($data, 'minimumOrderQuantity'),
            minimumStockQuantity: self::strOrNull($data, 'minimumStockQuantity'),
            targetStockQuantity:  self::strOrNull($data, 'targetStockQuantity'),
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
