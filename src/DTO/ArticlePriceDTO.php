<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a price entry for an Article.
 *
 * Maps to the articlePriceWithoutArticleReference schema in the weclapp API.
 * Instances are embedded inside ArticleDTO::$articlePrices.
 *
 * A price entry can be customer-specific, channel-specific, date-range-specific,
 * or quantity-scale-specific depending on the fields set.
 *
 * API key name on the parent article: articlePrices
 *
 * @see \miralsoft\weclapp\api\DTO\ArticleDTO
 */
final class ArticlePriceDTO extends AbstractDTO
{
    /**
     * @param string       $id                   Internal weclapp UUID (readOnly).
     * @param string       $version              Optimistic locking version string (readOnly).
     * @param int          $createdDate          Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate     Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $lastModifiedByUserId ID of the user who last modified this price (readOnly).
     * @param string|null  $price                Net price as a decimal string.
     * @param string|null  $currencyId           ID of the currency for this price.
     * @param string|null  $customerId           ID of the customer this price is specific to (null = all customers).
     * @param string|null  $salesChannel         Sales channel this price applies to (null = all channels).
     * @param string|null  $priceScaleType       Scale type — e.g. QUANTITY_BASED or SALES_VALUE_BASED.
     * @param string|null  $priceScaleValue      Threshold value for scale pricing (decimal string).
     * @param string|null  $description          Optional description for this price entry.
     * @param int|null     $startDate            Valid-from date in epoch milliseconds.
     * @param int|null     $endDate              Valid-until date in epoch milliseconds.
     * @param list<array>  $reductionAdditions   Surcharge/discount modifiers applied to this price (raw).
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $lastModifiedByUserId,
        public readonly ?string $price,
        public readonly ?string $currencyId,
        public readonly ?string $customerId,
        public readonly ?string $salesChannel,
        public readonly ?string $priceScaleType,
        public readonly ?string $priceScaleValue,
        public readonly ?string $description,
        public readonly ?int    $startDate,
        public readonly ?int    $endDate,
        public readonly array   $reductionAdditions,
    ) {}

    /**
     * Create an ArticlePriceDTO from a raw weclapp API response array.
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
            lastModifiedByUserId: self::strOrNull($data, 'lastModifiedByUserId'),
            price:                self::strOrNull($data, 'price'),
            currencyId:           self::strOrNull($data, 'currencyId'),
            customerId:           self::strOrNull($data, 'customerId'),
            salesChannel:         self::strOrNull($data, 'salesChannel'),
            priceScaleType:       self::strOrNull($data, 'priceScaleType'),
            priceScaleValue:      self::strOrNull($data, 'priceScaleValue'),
            description:          self::strOrNull($data, 'description'),
            startDate:            self::intOrNull($data, 'startDate'),
            endDate:              self::intOrNull($data, 'endDate'),
            reductionAdditions:   self::arr($data, 'reductionAdditions'),
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
