<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents an Article Category from the weclapp API.
 *
 * Article categories organise articles into a hierarchical tree structure.
 * They map to the /api/v2/articleCategory endpoint.
 * All 13 fields of the weclapp OpenAPI articleCategory schema are covered.
 *
 * @see \miralsoft\weclapp\api\Resource\ArticleCategoryResource
 */
final class ArticleCategoryDTO extends AbstractDTO
{
    /**
     * @param string       $id                              Internal weclapp UUID (readOnly).
     * @param string       $version                         Optimistic locking version string (readOnly).
     * @param int          $createdDate                     Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate                Last modification timestamp in epoch milliseconds (readOnly).
     * @param string       $name                            Display name of the category.
     * @param string|null  $description                     Optional description of the category.
     * @param string|null  $parentCategoryId                ID of the parent category (null for root categories).
     * @param string|null  $imageId                         ID of the category image (readOnly).
     * @param string|null  $articleAccountingCodeId         ID of the default accounting code for articles in this category.
     * @param string|null  $articleCategoryClassificationId ID of the category classification.
     * @param string|null  $costTypeId                      ID of the default cost type for articles in this category.
     * @param string|null  $salesCostCenterId               ID of the default sales cost centre.
     * @param string|null  $purchaseCostCenterId            ID of the default purchase cost centre.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $name,
        public readonly ?string $description,
        public readonly ?string $parentCategoryId,
        public readonly ?string $imageId,
        public readonly ?string $articleAccountingCodeId,
        public readonly ?string $articleCategoryClassificationId,
        public readonly ?string $costTypeId,
        public readonly ?string $salesCostCenterId,
        public readonly ?string $purchaseCostCenterId,
    ) {}

    /**
     * Create an ArticleCategoryDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                              self::str($data, 'id'),
            version:                         self::str($data, 'version'),
            createdDate:                     self::int($data, 'createdDate'),
            lastModifiedDate:                self::int($data, 'lastModifiedDate'),
            name:                            self::str($data, 'name'),
            description:                     self::strOrNull($data, 'description'),
            parentCategoryId:                self::strOrNull($data, 'parentCategoryId'),
            imageId:                         self::strOrNull($data, 'imageId'),
            articleAccountingCodeId:         self::strOrNull($data, 'articleAccountingCodeId'),
            articleCategoryClassificationId: self::strOrNull($data, 'articleCategoryClassificationId'),
            costTypeId:                      self::strOrNull($data, 'costTypeId'),
            salesCostCenterId:               self::strOrNull($data, 'salesCostCenterId'),
            purchaseCostCenterId:            self::strOrNull($data, 'purchaseCostCenterId'),
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
     * Returns true if this is a root category (has no parent).
     */
    public function isRootCategory(): bool
    {
        return $this->parentCategoryId === null;
    }
}
