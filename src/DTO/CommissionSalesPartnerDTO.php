<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents a commission assignment for a sales partner on a line item or document.
 *
 * Maps to the `commissionSalesPartner` schema. Embedded in sales order items,
 * sales invoice items, quotation items and top-level documents.
 */
final class CommissionSalesPartnerDTO extends AbstractDTO
{
    /**
     * @param string      $id                       Internal weclapp UUID (readOnly).
     * @param string      $version                  Optimistic locking version string (readOnly).
     * @param int         $createdDate              Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate         Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null $salesPartnerSupplierId   ID of the sales partner (supplier) entity.
     * @param string|null $commissionType           Commission type: "FIX" or "PERCENTAGE".
     * @param string|null $commissionFix            Fixed commission amount as a decimal string.
     * @param string|null $commissionPercentage     Commission percentage as a decimal string (e.g. "5.00").
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $salesPartnerSupplierId,
        public readonly ?string $commissionType,
        public readonly ?string $commissionFix,
        public readonly ?string $commissionPercentage,
    ) {}

    /**
     * Create a CommissionSalesPartnerDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                     self::str($data, 'id'),
            version:                self::str($data, 'version'),
            createdDate:            self::int($data, 'createdDate'),
            lastModifiedDate:       self::int($data, 'lastModifiedDate'),
            salesPartnerSupplierId: self::strOrNull($data, 'salesPartnerSupplierId'),
            commissionType:         self::strOrNull($data, 'commissionType'),
            commissionFix:          self::strOrNull($data, 'commissionFix'),
            commissionPercentage:   self::strOrNull($data, 'commissionPercentage'),
        );
    }

    /**
     * Returns the fixed commission amount as a float, or null if not set.
     */
    public function getCommissionFix(): ?float
    {
        return $this->commissionFix !== null ? (float) $this->commissionFix : null;
    }

    /**
     * Returns the commission percentage as a float, or null if not set.
     */
    public function getCommissionPercentage(): ?float
    {
        return $this->commissionPercentage !== null ? (float) $this->commissionPercentage : null;
    }
}
