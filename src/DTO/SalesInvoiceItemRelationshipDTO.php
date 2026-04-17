<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents the relationship between a sales invoice item and its source documents.
 *
 * Maps to the `salesInvoiceItemRelationship` schema. These are readOnly values
 * that describe the origin of an invoice line item — which sales order item,
 * shipment item or performance record item it was generated from.
 *
 * Instances are embedded inside SalesInvoiceItemDTO::$salesInvoiceItemRelationships.
 */
final class SalesInvoiceItemRelationshipDTO extends AbstractDTO
{
    /**
     * @param string|null $performanceRecordItemId  ID of the linked performance record item (readOnly).
     * @param string|null $quantity                 Quantity from this source as a decimal string (readOnly).
     * @param string|null $salesOrderItemId         ID of the originating sales order item (readOnly).
     * @param string|null $shipmentItemId           ID of the linked shipment item (readOnly).
     */
    public function __construct(
        public readonly ?string $performanceRecordItemId,
        public readonly ?string $quantity,
        public readonly ?string $salesOrderItemId,
        public readonly ?string $shipmentItemId,
    ) {}

    /**
     * Create a SalesInvoiceItemRelationshipDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            performanceRecordItemId: self::strOrNull($data, 'performanceRecordItemId'),
            quantity:                self::strOrNull($data, 'quantity'),
            salesOrderItemId:        self::strOrNull($data, 'salesOrderItemId'),
            shipmentItemId:          self::strOrNull($data, 'shipmentItemId'),
        );
    }

    /**
     * Returns the quantity as a float, or null if not set.
     */
    public function getQuantity(): ?float
    {
        return $this->quantity !== null ? (float) $this->quantity : null;
    }
}
