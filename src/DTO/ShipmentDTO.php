<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Shipment (outgoing delivery) from the weclapp API.
 *
 * Maps to the shipment schema. Shipments are created from Sales Orders and
 * represent the physical dispatch of goods. A shipment contains one or more
 * parcels (ShipmentDTO::$parcels) and line items (ShipmentDTO::$shipmentItems).
 *
 * PDF downloads (delivery note, picking list, shipping labels) are available
 * via ShipmentResource.
 *
 * @see \miralsoft\weclapp\api\Resource\ShipmentResource
 * @see \miralsoft\weclapp\api\DTO\ShipmentItemDTO
 * @see \miralsoft\weclapp\api\DTO\ParcelDTO
 */
final class ShipmentDTO extends AbstractDTO
{
    /**
     * @param string      $id                            Internal weclapp UUID (readOnly).
     * @param string      $version                       Optimistic locking version string (readOnly).
     * @param int         $createdDate                   Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate              Last modification timestamp in epoch milliseconds (readOnly).
     * @param string      $shipmentNumber                Human-readable shipment number (e.g. "L-10042").
     * @param string|null $status                        Shipment status (enum: shipmentStatusType).
     * @param string|null $shipmentType                  Shipment type (enum: shipmentOutType).
     * @param string|null $mainSalesOrderId              ID of the primary source sales order (readOnly).
     * @param string|null $creatorId                     ID of the user who created the shipment (readOnly).
     * @param string|null $responsibleUserId             ID of the responsible weclapp user.
     * @param string|null $description                   Internal description / comment.
     * @param string|null $additionalDeliveryInformation Additional delivery instructions.
     * @param string|null $commercialLanguage            Commercial language code.
     * @param string|null $consolidationStoragePlaceId   ID of the consolidation storage place.
     * @param string|null $customerPurchaseOrderNumber   Customer's purchase order reference number.
     * @param string|null $destinationStoragePlaceId     ID of the destination storage place.
     * @param string|null $destinationWarehouseId        ID of the destination warehouse.
     * @param string|null $dhlReceiverId                 DHL receiver account ID.
     * @param string|null $invoiceRecipientId            ID of the invoice recipient.
     * @param string|null $pickingInstructions           Special picking instructions.
     * @param string|null $shipmentMethodId              ID of the shipment method.
     * @param string|null $shippingCarrierId             ID of the primary shipping carrier.
     * @param string|null $shippingReturnCarrierId       ID of the return shipping carrier.
     * @param string|null $warehouseId                   ID of the source warehouse.
     * @param string|null $declaredValueAmount           Declared customs value as a decimal string.
     * @param string|null $declaredValueAmountCurrencyId Currency ID for the declared customs value.
     * @param string|null $packageReferenceNumber        Package reference number.
     * @param string|null $packageReturnTrackingNumber   Return tracking number for the package.
     * @param string|null $packageReturnTrackingUrl      Return tracking URL for the package.
     * @param string|null $packageTrackingNumber         Carrier tracking number for the package.
     * @param string|null $packageTrackingUrl            URL to track the package online.
     * @param string|null $packageWeight                 Total package weight as a decimal string (kg).
     * @param string|null $totalWeight                   Total shipment weight as a decimal string (kg, readOnly).
     * @param string|null $recordComment                 HTML comment on the record.
     * @param string|null $recordFreeText                HTML free text field on the record.
     * @param string|null $recordOpening                 HTML opening text on the record.
     * @param string|null $recipientCustomerNumber       Recipient's customer number (readOnly).
     * @param string|null $recipientPartyId              ID of the recipient party record.
     * @param string|null $recipientSupplierNumber       Recipient's supplier number (readOnly).
     * @param bool        $disableRecordEmailingRule     True if the automatic email rule is disabled.
     * @param bool        $picksComplete                 True if all picks for this shipment are complete (readOnly).
     * @param bool        $sentToRecipient               True if the shipment document has been sent to the recipient.
     * @param int|null    $deliveryDate                  Delivery date in epoch milliseconds.
     * @param int|null    $shippingDate                  Shipping / dispatch date in epoch milliseconds.
     * @param int         $packageHeight                 Package height in mm.
     * @param int         $packageLength                 Package length in mm.
     * @param int         $packageWidth                  Package width in mm.
     * @param int         $shippingLabelsCount           Number of shipping labels generated.
     * @param int         $shippingReturnLabelsCount     Number of return shipping labels generated.
     * @param RecordAddressDTO|null $invoiceAddress        Invoice address for this shipment.
     * @param RecordAddressDTO|null $recipientAddress     Recipient / delivery address.
     * @param RecordAddressDTO|null $shippedFromAddress   Address the goods were shipped from.
     * @param list<ShipmentItemDTO>    $shipmentItems         Line items of this shipment.
     * @param list<ParcelDTO>          $parcels               Parcel records with tracking information.
     * @param list<CustomAttributeDTO> $customAttributes      Custom attribute values.
     * @param list<array>              $purchaseOrders        Linked purchase order references (raw {id} objects).
     * @param list<array>              $salesOrders           Linked sales order references (raw {id} objects).
     * @param list<array>              $statusHistory         Status change history (raw, readOnly).
     * @param list<array>              $tags                  List of tag objects.
     * @param EmailAddressesDTO|null   $recordEmailAddresses  Record e-mail address overrides.
     * @param EmailAddressesDTO|null   $salesInvoiceEmailAddresses Sales invoice e-mail address overrides.
     */
    public function __construct(
        // Identity
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,

        // Core
        public readonly string  $shipmentNumber,
        public readonly ?string $status,
        public readonly ?string $shipmentType,
        public readonly ?string $mainSalesOrderId,
        public readonly ?string $creatorId,
        public readonly ?string $responsibleUserId,
        public readonly ?string $description,

        // Delivery info
        public readonly ?string $additionalDeliveryInformation,
        public readonly ?string $commercialLanguage,
        public readonly ?string $consolidationStoragePlaceId,
        public readonly ?string $customerPurchaseOrderNumber,
        public readonly ?string $destinationStoragePlaceId,
        public readonly ?string $destinationWarehouseId,
        public readonly ?string $dhlReceiverId,
        public readonly ?string $invoiceRecipientId,
        public readonly ?string $pickingInstructions,

        // Logistics references
        public readonly ?string $shipmentMethodId,
        public readonly ?string $shippingCarrierId,
        public readonly ?string $shippingReturnCarrierId,
        public readonly ?string $warehouseId,

        // Declared value / customs
        public readonly ?string $declaredValueAmount,
        public readonly ?string $declaredValueAmountCurrencyId,

        // Package tracking
        public readonly ?string $packageReferenceNumber,
        public readonly ?string $packageReturnTrackingNumber,
        public readonly ?string $packageReturnTrackingUrl,
        public readonly ?string $packageTrackingNumber,
        public readonly ?string $packageTrackingUrl,

        // Weight / dimensions
        public readonly ?string $packageWeight,
        public readonly ?string $totalWeight,
        public readonly int     $packageHeight,
        public readonly int     $packageLength,
        public readonly int     $packageWidth,

        // Label counts
        public readonly int     $shippingLabelsCount,
        public readonly int     $shippingReturnLabelsCount,

        // Record text
        public readonly ?string $recordComment,
        public readonly ?string $recordFreeText,
        public readonly ?string $recordOpening,

        // Recipient info (readOnly)
        public readonly ?string $recipientCustomerNumber,
        public readonly ?string $recipientPartyId,
        public readonly ?string $recipientSupplierNumber,

        // Flags
        public readonly bool    $disableRecordEmailingRule,
        public readonly bool    $picksComplete,
        public readonly bool    $sentToRecipient,

        // Dates
        public readonly ?int    $deliveryDate,
        public readonly ?int    $shippingDate,

        // Addresses (recordAddress schema)
        public readonly ?RecordAddressDTO $invoiceAddress,
        public readonly ?RecordAddressDTO $recipientAddress,
        public readonly ?RecordAddressDTO $shippedFromAddress,

        // Nested typed arrays
        public readonly array   $shipmentItems,
        public readonly array   $parcels,
        public readonly array   $customAttributes,
        public readonly array   $purchaseOrders,
        public readonly array   $salesOrders,
        public readonly array   $statusHistory,
        public readonly array   $tags,

        // Typed email address objects
        public readonly ?EmailAddressesDTO $recordEmailAddresses,
        public readonly ?EmailAddressesDTO $salesInvoiceEmailAddresses,
    ) {}

    /**
     * Create a ShipmentDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $invoiceAddress = isset($data['invoiceAddress']) && is_array($data['invoiceAddress'])
            ? RecordAddressDTO::fromArray($data['invoiceAddress'])
            : null;

        $recipientAddress = isset($data['recipientAddress']) && is_array($data['recipientAddress'])
            ? RecordAddressDTO::fromArray($data['recipientAddress'])
            : null;

        $shippedFromAddress = isset($data['shippedFromAddress']) && is_array($data['shippedFromAddress'])
            ? RecordAddressDTO::fromArray($data['shippedFromAddress'])
            : null;

        return new static(
            id:                            self::str($data, 'id'),
            version:                       self::str($data, 'version'),
            createdDate:                   self::int($data, 'createdDate'),
            lastModifiedDate:              self::int($data, 'lastModifiedDate'),

            shipmentNumber:                self::str($data, 'shipmentNumber'),
            status:                        self::strOrNull($data, 'status'),
            shipmentType:                  self::strOrNull($data, 'shipmentType'),
            mainSalesOrderId:              self::strOrNull($data, 'mainSalesOrderId'),
            creatorId:                     self::strOrNull($data, 'creatorId'),
            responsibleUserId:             self::strOrNull($data, 'responsibleUserId'),
            description:                   self::strOrNull($data, 'description'),

            additionalDeliveryInformation: self::strOrNull($data, 'additionalDeliveryInformation'),
            commercialLanguage:            self::strOrNull($data, 'commercialLanguage'),
            consolidationStoragePlaceId:   self::strOrNull($data, 'consolidationStoragePlaceId'),
            customerPurchaseOrderNumber:   self::strOrNull($data, 'customerPurchaseOrderNumber'),
            destinationStoragePlaceId:     self::strOrNull($data, 'destinationStoragePlaceId'),
            destinationWarehouseId:        self::strOrNull($data, 'destinationWarehouseId'),
            dhlReceiverId:                 self::strOrNull($data, 'dhlReceiverId'),
            invoiceRecipientId:            self::strOrNull($data, 'invoiceRecipientId'),
            pickingInstructions:           self::strOrNull($data, 'pickingInstructions'),

            shipmentMethodId:              self::strOrNull($data, 'shipmentMethodId'),
            shippingCarrierId:             self::strOrNull($data, 'shippingCarrierId'),
            shippingReturnCarrierId:       self::strOrNull($data, 'shippingReturnCarrierId'),
            warehouseId:                   self::strOrNull($data, 'warehouseId'),

            declaredValueAmount:           self::strOrNull($data, 'declaredValueAmount'),
            declaredValueAmountCurrencyId: self::strOrNull($data, 'declaredValueAmountCurrencyId'),

            packageReferenceNumber:        self::strOrNull($data, 'packageReferenceNumber'),
            packageReturnTrackingNumber:   self::strOrNull($data, 'packageReturnTrackingNumber'),
            packageReturnTrackingUrl:      self::strOrNull($data, 'packageReturnTrackingUrl'),
            packageTrackingNumber:         self::strOrNull($data, 'packageTrackingNumber'),
            packageTrackingUrl:            self::strOrNull($data, 'packageTrackingUrl'),

            packageWeight:                 self::strOrNull($data, 'packageWeight'),
            totalWeight:                   self::strOrNull($data, 'totalWeight'),
            packageHeight:                 self::int($data, 'packageHeight'),
            packageLength:                 self::int($data, 'packageLength'),
            packageWidth:                  self::int($data, 'packageWidth'),

            shippingLabelsCount:           self::int($data, 'shippingLabelsCount'),
            shippingReturnLabelsCount:     self::int($data, 'shippingReturnLabelsCount'),

            recordComment:                 self::strOrNull($data, 'recordComment'),
            recordFreeText:                self::strOrNull($data, 'recordFreeText'),
            recordOpening:                 self::strOrNull($data, 'recordOpening'),

            recipientCustomerNumber:       self::strOrNull($data, 'recipientCustomerNumber'),
            recipientPartyId:              self::strOrNull($data, 'recipientPartyId'),
            recipientSupplierNumber:       self::strOrNull($data, 'recipientSupplierNumber'),

            disableRecordEmailingRule:     self::bool($data, 'disableRecordEmailingRule'),
            picksComplete:                 self::bool($data, 'picksComplete'),
            sentToRecipient:               self::bool($data, 'sentToRecipient'),

            deliveryDate:                  self::intOrNull($data, 'deliveryDate'),
            shippingDate:                  self::intOrNull($data, 'shippingDate'),

            invoiceAddress:                $invoiceAddress,
            recipientAddress:              $recipientAddress,
            shippedFromAddress:            $shippedFromAddress,

            shipmentItems:                 array_map(
                static fn(array $item) => ShipmentItemDTO::fromArray($item),
                self::arr($data, 'shipmentItems'),
            ),
            parcels:                       array_map(
                static fn(array $item) => ParcelDTO::fromArray($item),
                self::arr($data, 'parcels'),
            ),
            customAttributes:              array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
            purchaseOrders:                self::arr($data, 'purchaseOrders'),
            salesOrders:                   self::arr($data, 'salesOrders'),
            statusHistory:                 self::arr($data, 'statusHistory'),
            tags:                          self::arr($data, 'tags'),

            recordEmailAddresses:          isset($data['recordEmailAddresses']) && is_array($data['recordEmailAddresses'])
                                               ? EmailAddressesDTO::fromArray($data['recordEmailAddresses'])
                                               : null,
            salesInvoiceEmailAddresses:    isset($data['salesInvoiceEmailAddresses']) && is_array($data['salesInvoiceEmailAddresses'])
                                               ? EmailAddressesDTO::fromArray($data['salesInvoiceEmailAddresses'])
                                               : null,
        );
    }

    /**
     * Returns true if the shipment has been fully dispatched (status-based check).
     */
    public function isDispatched(): bool
    {
        return $this->status === 'SHIPPED' || $this->status === 'PARTIALLY_SHIPPED';
    }

    /**
     * Returns the primary tracking URL for this shipment.
     * Uses the first parcel's tracking URL if the top-level URL is not set.
     */
    public function getTrackingUrl(): ?string
    {
        if ($this->packageTrackingUrl !== null) {
            return $this->packageTrackingUrl;
        }

        foreach ($this->parcels as $parcel) {
            if ($parcel instanceof ParcelDTO && $parcel->trackingUrl !== null) {
                return $parcel->trackingUrl;
            }
        }

        return null;
    }

    /**
     * Returns the total weight as a float (kg), or null if not set.
     */
    public function getTotalWeight(): ?float
    {
        return $this->totalWeight !== null ? (float) $this->totalWeight : null;
    }

    /**
     * Returns the package weight as a float (kg), or null if not set.
     */
    public function getPackageWeight(): ?float
    {
        return $this->packageWeight !== null ? (float) $this->packageWeight : null;
    }

    /**
     * Returns the shipping date as a DateTimeImmutable object.
     */
    public function getShippingDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['shippingDate' => $this->shippingDate], 'shippingDate');
    }

    /**
     * Returns the delivery date as a DateTimeImmutable object.
     */
    public function getDeliveryDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['deliveryDate' => $this->deliveryDate], 'deliveryDate');
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
