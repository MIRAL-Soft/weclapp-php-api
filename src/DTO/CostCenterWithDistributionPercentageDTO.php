<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents a cost centre allocation with a distribution percentage.
 *
 * Maps to the `costCenterWithDistributionPercentage` schema. Embedded inside
 * SalesInvoiceItemDTO::$costCenterItems to describe how costs are distributed
 * across multiple cost centres.
 */
final class CostCenterWithDistributionPercentageDTO extends AbstractDTO
{
    /**
     * @param string      $id                      Internal weclapp UUID (readOnly).
     * @param string      $version                 Optimistic locking version string (readOnly).
     * @param int         $createdDate             Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate        Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null $costCenterId            ID of the cost centre.
     * @param string|null $distributionPercentage  Percentage of the cost allocated to this centre as a decimal string.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $costCenterId,
        public readonly ?string $distributionPercentage,
    ) {}

    /**
     * Create a CostCenterWithDistributionPercentageDTO from a raw weclapp API response array.
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
            costCenterId:           self::strOrNull($data, 'costCenterId'),
            distributionPercentage: self::strOrNull($data, 'distributionPercentage'),
        );
    }

    /**
     * Returns the distribution percentage as a float, or null if not set.
     */
    public function getDistributionPercentage(): ?float
    {
        return $this->distributionPercentage !== null ? (float) $this->distributionPercentage : null;
    }
}
