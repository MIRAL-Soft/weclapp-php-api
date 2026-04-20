<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a single component entry in an Article's bill of materials.
 *
 * Maps to both the billOfMaterial and salesBillOfMaterialArticleItem schemas
 * in the weclapp API (they are structurally identical — 7 fields each).
 *
 * Used in two contexts:
 *   - ArticleDTO::$productionBillOfMaterialItems  (API key: productionBillOfMaterialItems)
 *   - ArticleDTO::$salesBillOfMaterialItems        (API key: salesBillOfMaterialItems)
 *
 * @see \miralsoft\weclapp\api\DTO\ArticleDTO
 */
final class BillOfMaterialItemDTO extends AbstractDTO
{
    /**
     * @param string       $id             Internal weclapp UUID (readOnly).
     * @param string       $version        Optimistic locking version string (readOnly).
     * @param int          $createdDate    Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $articleId      ID of the component article.
     * @param string|null  $quantity       Component quantity per finished unit (decimal string).
     * @param int          $positionNumber Display position within the BOM (readOnly).
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $articleId,
        public readonly ?string $quantity,
        public readonly int     $positionNumber,
    ) {}

    /**
     * Create a BillOfMaterialItemDTO from a raw weclapp API response array.
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
            articleId:        self::strOrNull($data, 'articleId'),
            quantity:         self::strOrNull($data, 'quantity'),
            positionNumber:   self::int($data, 'positionNumber'),
        );
    }

    /**
     * Returns the component quantity as a float, or null if not set.
     */
    public function getQuantity(): ?float
    {
        return $this->quantity !== null ? (float) $this->quantity : null;
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
