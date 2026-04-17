<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents a warehouse pick operation linked to a sales order item.
 *
 * Maps to the `itemPick` schema. Instances are embedded inside
 * SalesOrderItemDTO::$picks.
 */
final class ItemPickDTO extends AbstractDTO
{
    /**
     * @param string      $id                                  Internal weclapp UUID (readOnly).
     * @param string      $version                             Optimistic locking version string (readOnly).
     * @param int         $createdDate                         Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate                    Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null $batchNumber                         Batch/lot number for the picked item.
     * @param int|null    $bookedDate                          Date the pick was booked in epoch milliseconds (readOnly).
     * @param string|null $confirmedByUserId                   ID of the user who confirmed the pick (readOnly).
     * @param int|null    $confirmedDate                       Date the pick was confirmed in epoch milliseconds (readOnly).
     * @param string|null $internalTransportReferenceId        ID of the internal transport reference.
     * @param string|null $orderItemId                         ID of the parent sales order item.
     * @param string|null $quantity                            Picked quantity as a decimal string.
     * @param array       $serialNumbers                       List of serial numbers for the picked items.
     * @param string|null $sourceInternalTransportReferenceId  Source internal transport reference ID (readOnly).
     * @param string|null $sourceStoragePlaceId                Source storage place ID (readOnly).
     * @param string|null $storagePlaceId                      Target storage place ID.
     * @param string|null $transportationOrderId               ID of the linked transportation order (readOnly).
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $batchNumber,
        public readonly ?int    $bookedDate,
        public readonly ?string $confirmedByUserId,
        public readonly ?int    $confirmedDate,
        public readonly ?string $internalTransportReferenceId,
        public readonly ?string $orderItemId,
        public readonly ?string $quantity,
        public readonly array   $serialNumbers,
        public readonly ?string $sourceInternalTransportReferenceId,
        public readonly ?string $sourceStoragePlaceId,
        public readonly ?string $storagePlaceId,
        public readonly ?string $transportationOrderId,
    ) {}

    /**
     * Create an ItemPickDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                                 self::str($data, 'id'),
            version:                            self::str($data, 'version'),
            createdDate:                        self::int($data, 'createdDate'),
            lastModifiedDate:                   self::int($data, 'lastModifiedDate'),
            batchNumber:                        self::strOrNull($data, 'batchNumber'),
            bookedDate:                         self::intOrNull($data, 'bookedDate'),
            confirmedByUserId:                  self::strOrNull($data, 'confirmedByUserId'),
            confirmedDate:                      self::intOrNull($data, 'confirmedDate'),
            internalTransportReferenceId:       self::strOrNull($data, 'internalTransportReferenceId'),
            orderItemId:                        self::strOrNull($data, 'orderItemId'),
            quantity:                           self::strOrNull($data, 'quantity'),
            serialNumbers:                      self::arr($data, 'serialNumbers'),
            sourceInternalTransportReferenceId: self::strOrNull($data, 'sourceInternalTransportReferenceId'),
            sourceStoragePlaceId:               self::strOrNull($data, 'sourceStoragePlaceId'),
            storagePlaceId:                     self::strOrNull($data, 'storagePlaceId'),
            transportationOrderId:              self::strOrNull($data, 'transportationOrderId'),
        );
    }

    /**
     * Returns the picked quantity as a float, or null if not set.
     */
    public function getQuantity(): ?float
    {
        return $this->quantity !== null ? (float) $this->quantity : null;
    }
}
