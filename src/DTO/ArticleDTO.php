<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents an Article (product) record from the weclapp API.
 *
 * Articles map to the /api/v2/article endpoint.
 *
 * @see \miralsoft\weclapp\api\Resource\ArticleResource
 */
final class ArticleDTO extends AbstractDTO
{
    /**
     * @param string       $id                   Internal weclapp UUID.
     * @param string       $version              Optimistic locking version string.
     * @param int          $createdDate          Creation timestamp in epoch milliseconds.
     * @param int          $lastModifiedDate     Last modification timestamp in epoch milliseconds.
     * @param string       $articleNumber        Unique article / SKU number (e.g. "ART-10042").
     * @param string       $name                 Short article name / title.
     * @param string|null  $description          Short description.
     * @param string|null  $descriptionLong      Long / HTML description.
     * @param bool         $active               Whether the article is active.
     * @param bool         $sellable             Whether the article can be sold.
     * @param bool         $purchasable          Whether the article can be purchased.
     * @param bool         $stockable            Whether the article is tracked in stock.
     * @param bool         $serialNumberRequired Whether serial numbers are required.
     * @param bool         $batchNumberRequired  Whether batch numbers are required.
     * @param float|null   $salesPrice           Net sales price.
     * @param float|null   $purchasePrice        Net purchase price.
     * @param float|null   $availableStock       Current available stock quantity.
     * @param float|null   $reservedStock        Currently reserved stock quantity.
     * @param string|null  $unit                 Unit of measure (e.g. "Stk", "kg").
     * @param string|null  $articleCategoryId    ID of the assigned article category.
     * @param string|null  $articleCategoryName  Name of the assigned article category.
     * @param list<array>  $tags                 List of tag objects.
     * @param list<array>  $customAttributes     List of custom attribute objects.
     * @param list<array>  $articleImages        List of article image objects.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $articleNumber,
        public readonly string  $name,
        public readonly ?string $description,
        public readonly ?string $descriptionLong,
        public readonly bool    $active,
        public readonly bool    $sellable,
        public readonly bool    $purchasable,
        public readonly bool    $stockable,
        public readonly bool    $serialNumberRequired,
        public readonly bool    $batchNumberRequired,
        public readonly ?float  $salesPrice,
        public readonly ?float  $purchasePrice,
        public readonly ?float  $availableStock,
        public readonly ?float  $reservedStock,
        public readonly ?string $unit,
        public readonly ?string $articleCategoryId,
        public readonly ?string $articleCategoryName,
        public readonly array   $tags,
        public readonly array   $customAttributes,
        public readonly array   $articleImages,
    ) {}

    /**
     * Create an ArticleDTO from a raw weclapp API response array.
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
            articleNumber:        self::str($data, 'articleNumber'),
            name:                 self::str($data, 'name'),
            description:          self::strOrNull($data, 'description'),
            descriptionLong:      self::strOrNull($data, 'descriptionLong'),
            active:               self::bool($data, 'active', true),
            sellable:             self::bool($data, 'sellable', true),
            purchasable:          self::bool($data, 'purchasable'),
            stockable:            self::bool($data, 'stockable'),
            serialNumberRequired: self::bool($data, 'serialNumberRequired'),
            batchNumberRequired:  self::bool($data, 'batchNumberRequired'),
            salesPrice:           self::floatOrNull($data, 'salesPrice'),
            purchasePrice:        self::floatOrNull($data, 'purchasePrice'),
            availableStock:       self::floatOrNull($data, 'availableStock'),
            reservedStock:        self::floatOrNull($data, 'reservedStock'),
            unit:                 self::strOrNull($data, 'unit'),
            articleCategoryId:    self::strOrNull($data, 'articleCategoryId'),
            articleCategoryName:  self::strOrNull($data, 'articleCategoryName'),
            tags:                 self::arr($data, 'tags'),
            customAttributes:     self::arr($data, 'customAttributes'),
            articleImages:        self::arr($data, 'articleImages'),
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

    /**
     * Returns true if the article has available stock (stock > 0).
     */
    public function isInStock(): bool
    {
        return ($this->availableStock ?? 0.0) > 0.0;
    }
}
