<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a single line item within a Purchase Order in the weclapp API.
 *
 * Maps to the purchaseOrderItem schema. Instances are embedded inside
 * PurchaseOrderDTO::$purchaseOrderItems.
 *
 * @see \miralsoft\weclapp\api\DTO\PurchaseOrderDTO
 */
final class PurchaseOrderItemDTO extends AbstractDTO
{
    /**
     * @param string      $id                                       Internal weclapp UUID (readOnly).
     * @param string      $version                                  Record version (optimistic locking, readOnly).
     * @param int         $createdDate                              Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate                         Last modification timestamp in epoch milliseconds (readOnly).
     * @param bool        $addPageBreakBefore                       Insert a page break before this item in the PDF.
     * @param string|null $articleId                                ID of the linked article.
     * @param string|null $articleSupplySourceId                    ID of the linked article supply source.
     * @param string|null $blanketPurchaseOrderId                   ID of the blanket purchase order.
     * @param string|null $blanketPurchaseOrderReleaseId            ID of the blanket purchase order release.
     * @param string|null $description                              HTML description of the line item.
     * @param bool        $descriptionFixed                         If true, the description is locked.
     * @param string|null $discountPercentage                       Discount percentage as a decimal string.
     * @param string|null $grossAmount                              Total gross amount (readOnly).
     * @param string|null $grossAmountInCompanyCurrency             Gross amount in company currency (readOnly).
     * @param string|null $groupName                                Group header this item belongs to.
     * @param string|null $invoicedQuantity                         Quantity already invoiced (readOnly).
     * @param string|null $itemType                                 Item type (enum: itemType).
     * @param bool        $manualQuantity                           If true, the quantity was entered manually.
     * @param bool        $manualUnitPrice                          If true, the unit price was entered manually.
     * @param string|null $netAmount                                Total net amount (readOnly).
     * @param string|null $netAmountForStatistics                   Net amount for statistics (readOnly).
     * @param string|null $netAmountForStatisticsInCompanyCurrency  Net amount for statistics in company currency (readOnly).
     * @param string|null $netAmountInCompanyCurrency               Net amount in company currency (readOnly).
     * @param string|null $note                                     Internal note.
     * @param string|null $parentItemId                             ID of the parent item (for sub-positions).
     * @param int|null    $plannedDeliveryDate                      Planned delivery date in epoch milliseconds.
     * @param int|null    $plannedShippingDate                      Planned shipping date in epoch milliseconds.
     * @param int         $positionNumber                           Display position within the order (1-based).
     * @param string|null $purchaseOrderRequestOfferItemId          ID of the linked purchase order request offer item.
     * @param string|null $quantity                                 Ordered quantity as a decimal string.
     * @param string|null $receivedQuantity                         Quantity already received (readOnly).
     * @param string|null $salesOrderItemId                         ID of the linked sales order item (for dropshipping).
     * @param int|null    $servicePeriodFromDate                    Service period start date in epoch milliseconds.
     * @param int|null    $servicePeriodToDate                      Service period end date in epoch milliseconds.
     * @param string|null $taxId                                    ID of the applied tax rate.
     * @param string|null $title                                    Line item title / article name.
     * @param string|null $unitId                                   ID of the unit of measure.
     * @param string|null $unitPrice                                Purchase unit price as a decimal string.
     * @param string|null $unitPriceInCompanyCurrency               Unit price in company currency (readOnly).
     * @param list<ReductionAdditionItemDTO> $reductionAdditionItems Surcharge / discount sub-items.
     * @param list<array>                    $batchSerialNumbers     Batch/serial number entries (raw, complex).
     * @param list<CustomAttributeDTO>       $customAttributes       Custom attribute values.
     */
    public function __construct(
        // Identity
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,

        // Article reference
        public readonly ?string $articleId,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly bool    $descriptionFixed,

        // Quantities & pricing
        public readonly ?string $quantity,
        public readonly ?string $unitId,
        public readonly ?string $unitPrice,
        public readonly ?string $unitPriceInCompanyCurrency,
        public readonly ?string $discountPercentage,

        // Computed amounts (readOnly)
        public readonly ?string $grossAmount,
        public readonly ?string $grossAmountInCompanyCurrency,
        public readonly ?string $netAmount,
        public readonly ?string $netAmountInCompanyCurrency,
        public readonly ?string $netAmountForStatistics,
        public readonly ?string $netAmountForStatisticsInCompanyCurrency,

        // Fulfillment state (readOnly)
        public readonly ?string $invoicedQuantity,
        public readonly ?string $receivedQuantity,

        // Item classification
        public readonly int     $positionNumber,
        public readonly ?string $itemType,
        public readonly ?string $note,
        public readonly ?string $groupName,
        public readonly ?string $parentItemId,
        public readonly bool    $addPageBreakBefore,
        public readonly ?string $taxId,

        // Manual override flags
        public readonly bool    $manualQuantity,
        public readonly bool    $manualUnitPrice,

        // Dates
        public readonly ?int    $plannedDeliveryDate,
        public readonly ?int    $plannedShippingDate,
        public readonly ?int    $servicePeriodFromDate,
        public readonly ?int    $servicePeriodToDate,

        // References
        public readonly ?string $articleSupplySourceId,
        public readonly ?string $blanketPurchaseOrderId,
        public readonly ?string $blanketPurchaseOrderReleaseId,
        public readonly ?string $purchaseOrderRequestOfferItemId,
        public readonly ?string $salesOrderItemId,

        // Nested typed arrays
        public readonly array   $reductionAdditionItems,
        public readonly array   $batchSerialNumbers,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a PurchaseOrderItemDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                                      self::str($data, 'id'),
            version:                                 self::str($data, 'version'),
            createdDate:                             self::int($data, 'createdDate'),
            lastModifiedDate:                        self::int($data, 'lastModifiedDate'),

            articleId:                               self::strOrNull($data, 'articleId'),
            title:                                   self::strOrNull($data, 'title'),
            description:                             self::strOrNull($data, 'description'),
            descriptionFixed:                        self::bool($data, 'descriptionFixed'),

            quantity:                                self::strOrNull($data, 'quantity'),
            unitId:                                  self::strOrNull($data, 'unitId'),
            unitPrice:                               self::strOrNull($data, 'unitPrice'),
            unitPriceInCompanyCurrency:              self::strOrNull($data, 'unitPriceInCompanyCurrency'),
            discountPercentage:                      self::strOrNull($data, 'discountPercentage'),

            grossAmount:                             self::strOrNull($data, 'grossAmount'),
            grossAmountInCompanyCurrency:            self::strOrNull($data, 'grossAmountInCompanyCurrency'),
            netAmount:                               self::strOrNull($data, 'netAmount'),
            netAmountInCompanyCurrency:              self::strOrNull($data, 'netAmountInCompanyCurrency'),
            netAmountForStatistics:                  self::strOrNull($data, 'netAmountForStatistics'),
            netAmountForStatisticsInCompanyCurrency: self::strOrNull($data, 'netAmountForStatisticsInCompanyCurrency'),

            invoicedQuantity:                        self::strOrNull($data, 'invoicedQuantity'),
            receivedQuantity:                        self::strOrNull($data, 'receivedQuantity'),

            positionNumber:                          self::int($data, 'positionNumber'),
            itemType:                                self::strOrNull($data, 'itemType'),
            note:                                    self::strOrNull($data, 'note'),
            groupName:                               self::strOrNull($data, 'groupName'),
            parentItemId:                            self::strOrNull($data, 'parentItemId'),
            addPageBreakBefore:                      self::bool($data, 'addPageBreakBefore'),
            taxId:                                   self::strOrNull($data, 'taxId'),

            manualQuantity:                          self::bool($data, 'manualQuantity'),
            manualUnitPrice:                         self::bool($data, 'manualUnitPrice'),

            plannedDeliveryDate:                     self::intOrNull($data, 'plannedDeliveryDate'),
            plannedShippingDate:                     self::intOrNull($data, 'plannedShippingDate'),
            servicePeriodFromDate:                   self::intOrNull($data, 'servicePeriodFromDate'),
            servicePeriodToDate:                     self::intOrNull($data, 'servicePeriodToDate'),

            articleSupplySourceId:                   self::strOrNull($data, 'articleSupplySourceId'),
            blanketPurchaseOrderId:                  self::strOrNull($data, 'blanketPurchaseOrderId'),
            blanketPurchaseOrderReleaseId:           self::strOrNull($data, 'blanketPurchaseOrderReleaseId'),
            purchaseOrderRequestOfferItemId:         self::strOrNull($data, 'purchaseOrderRequestOfferItemId'),
            salesOrderItemId:                        self::strOrNull($data, 'salesOrderItemId'),

            reductionAdditionItems:                  array_map(
                static fn(array $item) => ReductionAdditionItemDTO::fromArray($item),
                self::arr($data, 'reductionAdditionItems'),
            ),
            batchSerialNumbers:                      self::arr($data, 'batchSerialNumbers'),
            customAttributes:                        array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
        );
    }

    /**
     * Returns the ordered quantity as a float, or null if not set.
     */
    public function getQuantity(): ?float
    {
        return $this->quantity !== null ? (float) $this->quantity : null;
    }

    /**
     * Returns the net unit price as a float, or null if not set.
     */
    public function getUnitPrice(): ?float
    {
        return $this->unitPrice !== null ? (float) $this->unitPrice : null;
    }

    /**
     * Returns the net amount for this line item as a float, or null if not set.
     */
    public function getNetAmount(): ?float
    {
        return $this->netAmount !== null ? (float) $this->netAmount : null;
    }

    /**
     * Returns the service period start date as a DateTimeImmutable object.
     */
    public function getServicePeriodFromDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['servicePeriodFromDate' => $this->servicePeriodFromDate], 'servicePeriodFromDate');
    }

    /**
     * Returns the service period end date as a DateTimeImmutable object.
     */
    public function getServicePeriodToDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['servicePeriodToDate' => $this->servicePeriodToDate], 'servicePeriodToDate');
    }
}
