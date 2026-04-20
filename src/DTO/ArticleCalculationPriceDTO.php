<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a calculation price entry for an Article.
 *
 * Maps to the articleCalculationPrice schema in the weclapp API.
 * Calculation prices are used for purchase/cost price calculations
 * and may be time-limited and/or channel-specific.
 *
 * Instances are embedded inside ArticleDTO::$articleCalculationPrices.
 *
 * API key name on the parent article: articleCalculationPrices
 *
 * @see \miralsoft\weclapp\api\DTO\ArticleDTO
 */
final class ArticleCalculationPriceDTO extends AbstractDTO
{
    /**
     * @param string       $id                          Internal weclapp UUID (readOnly).
     * @param string       $version                     Optimistic locking version string (readOnly).
     * @param int          $createdDate                 Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate            Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $articleCalculationPriceType Type of the calculation price (enum).
     * @param string|null  $price                       Price as a decimal string.
     * @param string|null  $salesChannel                Sales channel this price applies to.
     * @param int|null     $startDate                   Valid-from date in epoch milliseconds.
     * @param int|null     $endDate                     Valid-until date in epoch milliseconds.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $articleCalculationPriceType,
        public readonly ?string $price,
        public readonly ?string $salesChannel,
        public readonly ?int    $startDate,
        public readonly ?int    $endDate,
    ) {}

    /**
     * Create an ArticleCalculationPriceDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                          self::str($data, 'id'),
            version:                     self::str($data, 'version'),
            createdDate:                 self::int($data, 'createdDate'),
            lastModifiedDate:            self::int($data, 'lastModifiedDate'),
            articleCalculationPriceType: self::strOrNull($data, 'articleCalculationPriceType'),
            price:                       self::strOrNull($data, 'price'),
            salesChannel:                self::strOrNull($data, 'salesChannel'),
            startDate:                   self::intOrNull($data, 'startDate'),
            endDate:                     self::intOrNull($data, 'endDate'),
        );
    }

    /**
     * Returns the price as a float, or null if not set.
     */
    public function getPrice(): ?float
    {
        return $this->price !== null ? (float) $this->price : null;
    }

    /**
     * Returns the start date as a DateTimeImmutable object.
     */
    public function getStartDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['startDate' => $this->startDate], 'startDate');
    }

    /**
     * Returns the end date as a DateTimeImmutable object.
     */
    public function getEndDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['endDate' => $this->endDate], 'endDate');
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
