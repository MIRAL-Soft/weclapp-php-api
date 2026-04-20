<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a unit-of-measure quantity conversion for an Article.
 *
 * Maps to the quantityConversion schema in the weclapp API.
 * Instances are embedded inside ArticleDTO::$quantityConversions.
 *
 * API key name on the parent article: quantityConversions
 *
 * @see \miralsoft\weclapp\api\DTO\ArticleDTO
 */
final class QuantityConversionDTO extends AbstractDTO
{
    /**
     * @param string       $id                  Internal weclapp UUID (readOnly).
     * @param string       $version             Optimistic locking version string (readOnly).
     * @param int          $createdDate         Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate    Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $createdUserId       ID of the user who created this conversion (readOnly).
     * @param string|null  $lastEditedUserId    ID of the user who last edited this conversion (readOnly).
     * @param string|null  $unitId              ID of the alternative unit of measure.
     * @param string|null  $conversionQuantity  How many base-unit items equal one alternative-unit item (decimal string).
     * @param bool         $oppositeDirection   If true, the conversion applies in the opposite direction.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $createdUserId,
        public readonly ?string $lastEditedUserId,
        public readonly ?string $unitId,
        public readonly ?string $conversionQuantity,
        public readonly bool    $oppositeDirection,
    ) {}

    /**
     * Create a QuantityConversionDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                 self::str($data, 'id'),
            version:            self::str($data, 'version'),
            createdDate:        self::int($data, 'createdDate'),
            lastModifiedDate:   self::int($data, 'lastModifiedDate'),
            createdUserId:      self::strOrNull($data, 'createdUserId'),
            lastEditedUserId:   self::strOrNull($data, 'lastEditedUserId'),
            unitId:             self::strOrNull($data, 'unitId'),
            conversionQuantity: self::strOrNull($data, 'conversionQuantity'),
            oppositeDirection:  self::bool($data, 'oppositeDirection'),
        );
    }

    /**
     * Returns the conversion quantity as a float, or null if not set.
     */
    public function getConversionQuantity(): ?float
    {
        return $this->conversionQuantity !== null ? (float) $this->conversionQuantity : null;
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
