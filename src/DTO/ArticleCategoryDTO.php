<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents an Article Category from the weclapp API.
 *
 * Article categories organise articles into a hierarchical tree structure.
 * They map to the /api/v2/articleCategory endpoint.
 *
 * @see \miralsoft\weclapp\api\Resource\ArticleCategoryResource
 */
final class ArticleCategoryDTO extends AbstractDTO
{
    /**
     * @param string      $id                  Internal weclapp UUID.
     * @param string      $version             Optimistic locking version string.
     * @param int         $createdDate         Creation timestamp in epoch milliseconds.
     * @param int         $lastModifiedDate    Last modification timestamp in epoch milliseconds.
     * @param string      $name                Display name of the category.
     * @param bool        $active              Whether the category is active.
     * @param string|null $parentCategoryId    ID of the parent category (null for root categories).
     * @param string|null $parentCategoryName  Name of the parent category.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $name,
        public readonly bool    $active,
        public readonly ?string $parentCategoryId,
        public readonly ?string $parentCategoryName,
    ) {}

    /**
     * Create an ArticleCategoryDTO from a raw weclapp API response array.
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
            name:               self::str($data, 'name'),
            active:             self::bool($data, 'active', true),
            parentCategoryId:   self::strOrNull($data, 'parentCategoryId'),
            parentCategoryName: self::strOrNull($data, 'parentCategoryName'),
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
