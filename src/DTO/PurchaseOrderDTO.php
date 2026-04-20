<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Purchase Order from the weclapp API.
 *
 * Maps to the purchaseOrder schema. Purchase orders are sent to suppliers
 * to order goods or services. They link to incoming goods records on delivery.
 *
 * PDF downloads (purchase order PDF, cancellation slip) are available
 * via PurchaseOrderResource.
 *
 * @see \miralsoft\weclapp\api\Resource\PurchaseOrderResource
 * @see \miralsoft\weclapp\api\DTO\PurchaseOrderItemDTO
 */
final class PurchaseOrderDTO extends AbstractDTO
{
    /**
     * @param string      $id                                       Internal weclapp UUID (readOnly).
     * @param string      $version                                  Optimistic locking version string (readOnly).
     * @param int         $createdDate                              Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate                         Last modification timestamp in epoch milliseconds (readOnly).
     * @param string      $purchaseOrderNumber                      Human-readable order number (e.g. "BE-10042").
     * @param string|null $status                                   Order status (enum: supplierOrderStatusType).
     * @param string|null $purchaseOrderType                        Order type (enum: supplierOrderType).
     * @param string      $supplierId                               ID of the linked supplier.
     * @param string|null $creatorId                                ID of the user who created the order (readOnly).
     * @param string|null $responsibleUserId                        ID of the responsible weclapp user.
     * @param string|null $description                              Internal description / comment.
     * @param string|null $note                                     Internal note.
     * @param string|null $advancePaymentStatus                     Advance payment status (enum: advancePaymentStatus, readOnly).
     * @param string|null $commercialLanguage                       Commercial language code.
     * @param string|null $commercialLanguageCustomer               Commercial language for customer documents.
     * @param string|null $commission                               Commission note or identifier.
     * @param string|null $confirmationNumber                       Supplier order confirmation number.
     * @param string|null $externalPurchaseOrderNumber              External / supplier's own order number.
     * @param string|null $supplierQuotationNumber                  Supplier quotation reference number.
     * @param string|null $formSettingsFromSalesChannel             Sales channel used for form settings (enum: distributionChannel).
     * @param string|null $currencyConversionRate                   Currency conversion rate as a decimal string.
     * @param int|null    $currencyConversionDate                   Date of currency conversion in epoch milliseconds (readOnly).
     * @param bool        $currencyConversionLocked                 True if the currency conversion rate is locked.
     * @param string|null $recordCurrencyId                         ID of the document currency.
     * @param string|null $netAmount                                Net order amount as a decimal string (readOnly).
     * @param string|null $grossAmount                              Gross order amount as a decimal string (readOnly).
     * @param string|null $netAmountInCompanyCurrency               Net amount in company currency (readOnly).
     * @param string|null $grossAmountInCompanyCurrency             Gross amount in company currency (readOnly).
     * @param string|null $headerDiscount                           Header-level discount percentage.
     * @param string|null $headerSurcharge                          Header-level surcharge percentage.
     * @param string|null $nonStandardTaxId                         ID of a non-standard tax rate.
     * @param string|null $paymentMethodId                          ID of the payment method.
     * @param string|null $termOfPaymentId                          ID of the term of payment.
     * @param string|null $shipmentMethodId                         ID of the shipment method.
     * @param string|null $shippingCarrierId                        ID of the shipping carrier.
     * @param string|null $warehouseId                              ID of the receiving warehouse.
     * @param string|null $salesOrderId                             ID of the originating sales order (for dropshipping).
     * @param string|null $purchaseOrderRequestId                   ID of the originating purchase order request.
     * @param string|null $mergedToPurchaseOrderId                  ID of the order this was merged into (readOnly).
     * @param string|null $supplierHabitualExporterLetterOfIntentId ID of the habitual exporter letter of intent.
     * @param string|null $packageTrackingNumber                    Carrier tracking number for the delivery.
     * @param string|null $packageTrackingUrl                       URL to track the delivery online.
     * @param string|null $recipientCountryCode                     Recipient country code (enum: country).
     * @param string|null $senderCountryCode                        Sender country code (enum: country).
     * @param int         $orderDate                                Order date in epoch milliseconds.
     * @param int|null    $plannedDeliveryDate                      Planned delivery date in epoch milliseconds.
     * @param int|null    $plannedShippingDate                      Planned shipping date in epoch milliseconds.
     * @param int|null    $servicePeriodFrom                        Service period start date in epoch milliseconds.
     * @param int|null    $servicePeriodTo                          Service period end date in epoch milliseconds.
     * @param int|null    $shippingNotificationDate                 Shipping notification date in epoch milliseconds.
     * @param bool        $disableRecordEmailingRule                True if the automatic email rule is disabled.
     * @param bool        $includeCashDiscountInValuationPrice      True if cash discount is included in valuation price.
     * @param bool        $invoiced                                 True if the order has been fully invoiced (readOnly).
     * @param bool        $paid                                     True if the order has been paid (readOnly).
     * @param bool        $received                                 True if all goods have been received (readOnly).
     * @param bool        $sentToRecipient                          True if the order document has been sent to the supplier.
     * @param string|null $recordComment                            HTML comment on the record.
     * @param string|null $recordFreeText                           HTML free text field on the record.
     * @param string|null $recordOpening                            HTML opening text on the record.
     * @param RecordAddressDTO|null $deliveryAddress                 Delivery address for this order.
     * @param RecordAddressDTO|null $invoiceAddress                  Invoice address for this order.
     * @param RecordAddressDTO|null $recordAddress                   Record address for this order.
     * @param list<PurchaseOrderItemDTO> $purchaseOrderItems         Line items of this order.
     * @param list<ShippingCostItemDTO>  $shippingCostItems          Shipping cost items.
     * @param list<CustomAttributeDTO>   $customAttributes           Custom attribute values.
     * @param list<array>                $statusHistory              Status change history (raw, readOnly).
     * @param list<array>                $tags                       List of tag objects.
     * @param list<array>                $dropshippingDeliveryNoteFormTexts Dropshipping delivery note form texts (raw).
     * @param EmailAddressesDTO|null     $recordEmailAddresses        Record e-mail address overrides.
     */
    public function __construct(
        // Identity
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,

        // Core
        public readonly string  $purchaseOrderNumber,
        public readonly ?string $status,
        public readonly ?string $purchaseOrderType,
        public readonly string  $supplierId,
        public readonly ?string $creatorId,
        public readonly ?string $responsibleUserId,
        public readonly ?string $description,
        public readonly ?string $note,
        public readonly ?string $advancePaymentStatus,

        // Commercial
        public readonly ?string $commercialLanguage,
        public readonly ?string $commercialLanguageCustomer,
        public readonly ?string $commission,
        public readonly ?string $confirmationNumber,
        public readonly ?string $externalPurchaseOrderNumber,
        public readonly ?string $supplierQuotationNumber,
        public readonly ?string $formSettingsFromSalesChannel,

        // Currency
        public readonly ?string $currencyConversionRate,
        public readonly ?int    $currencyConversionDate,
        public readonly bool    $currencyConversionLocked,
        public readonly ?string $recordCurrencyId,

        // Amounts
        public readonly ?string $netAmount,
        public readonly ?string $grossAmount,
        public readonly ?string $netAmountInCompanyCurrency,
        public readonly ?string $grossAmountInCompanyCurrency,
        public readonly ?string $headerDiscount,
        public readonly ?string $headerSurcharge,
        public readonly ?string $nonStandardTaxId,

        // References
        public readonly ?string $paymentMethodId,
        public readonly ?string $termOfPaymentId,
        public readonly ?string $shipmentMethodId,
        public readonly ?string $shippingCarrierId,
        public readonly ?string $warehouseId,
        public readonly ?string $salesOrderId,
        public readonly ?string $purchaseOrderRequestId,
        public readonly ?string $mergedToPurchaseOrderId,
        public readonly ?string $supplierHabitualExporterLetterOfIntentId,

        // Tracking
        public readonly ?string $packageTrackingNumber,
        public readonly ?string $packageTrackingUrl,
        public readonly ?string $recipientCountryCode,
        public readonly ?string $senderCountryCode,

        // Dates
        public readonly int     $orderDate,
        public readonly ?int    $plannedDeliveryDate,
        public readonly ?int    $plannedShippingDate,
        public readonly ?int    $servicePeriodFrom,
        public readonly ?int    $servicePeriodTo,
        public readonly ?int    $shippingNotificationDate,

        // Flags
        public readonly bool    $disableRecordEmailingRule,
        public readonly bool    $includeCashDiscountInValuationPrice,
        public readonly bool    $invoiced,
        public readonly bool    $paid,
        public readonly bool    $received,
        public readonly bool    $sentToRecipient,

        // Record text
        public readonly ?string $recordComment,
        public readonly ?string $recordFreeText,
        public readonly ?string $recordOpening,

        // Addresses (recordAddress schema)
        public readonly ?RecordAddressDTO $deliveryAddress,
        public readonly ?RecordAddressDTO $invoiceAddress,
        public readonly ?RecordAddressDTO $recordAddress,

        // Nested typed arrays
        public readonly array   $purchaseOrderItems,
        public readonly array   $shippingCostItems,
        public readonly array   $customAttributes,
        public readonly array   $statusHistory,
        public readonly array   $tags,
        public readonly array   $dropshippingDeliveryNoteFormTexts,

        // Typed email address object
        public readonly ?EmailAddressesDTO $recordEmailAddresses,
    ) {}

    /**
     * Create a PurchaseOrderDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $deliveryAddress = isset($data['deliveryAddress']) && is_array($data['deliveryAddress'])
            ? RecordAddressDTO::fromArray($data['deliveryAddress'])
            : null;

        $invoiceAddress = isset($data['invoiceAddress']) && is_array($data['invoiceAddress'])
            ? RecordAddressDTO::fromArray($data['invoiceAddress'])
            : null;

        $recordAddress = isset($data['recordAddress']) && is_array($data['recordAddress'])
            ? RecordAddressDTO::fromArray($data['recordAddress'])
            : null;

        return new static(
            id:                                       self::str($data, 'id'),
            version:                                  self::str($data, 'version'),
            createdDate:                              self::int($data, 'createdDate'),
            lastModifiedDate:                         self::int($data, 'lastModifiedDate'),

            purchaseOrderNumber:                      self::str($data, 'purchaseOrderNumber'),
            status:                                   self::strOrNull($data, 'status'),
            purchaseOrderType:                        self::strOrNull($data, 'purchaseOrderType'),
            supplierId:                               self::str($data, 'supplierId'),
            creatorId:                                self::strOrNull($data, 'creatorId'),
            responsibleUserId:                        self::strOrNull($data, 'responsibleUserId'),
            description:                              self::strOrNull($data, 'description'),
            note:                                     self::strOrNull($data, 'note'),
            advancePaymentStatus:                     self::strOrNull($data, 'advancePaymentStatus'),

            commercialLanguage:                       self::strOrNull($data, 'commercialLanguage'),
            commercialLanguageCustomer:               self::strOrNull($data, 'commercialLanguageCustomer'),
            commission:                               self::strOrNull($data, 'commission'),
            confirmationNumber:                       self::strOrNull($data, 'confirmationNumber'),
            externalPurchaseOrderNumber:              self::strOrNull($data, 'externalPurchaseOrderNumber'),
            supplierQuotationNumber:                  self::strOrNull($data, 'supplierQuotationNumber'),
            formSettingsFromSalesChannel:             self::strOrNull($data, 'formSettingsFromSalesChannel'),

            currencyConversionRate:                   self::strOrNull($data, 'currencyConversionRate'),
            currencyConversionDate:                   self::intOrNull($data, 'currencyConversionDate'),
            currencyConversionLocked:                 self::bool($data, 'currencyConversionLocked'),
            recordCurrencyId:                         self::strOrNull($data, 'recordCurrencyId'),

            netAmount:                                self::strOrNull($data, 'netAmount'),
            grossAmount:                              self::strOrNull($data, 'grossAmount'),
            netAmountInCompanyCurrency:               self::strOrNull($data, 'netAmountInCompanyCurrency'),
            grossAmountInCompanyCurrency:             self::strOrNull($data, 'grossAmountInCompanyCurrency'),
            headerDiscount:                           self::strOrNull($data, 'headerDiscount'),
            headerSurcharge:                          self::strOrNull($data, 'headerSurcharge'),
            nonStandardTaxId:                         self::strOrNull($data, 'nonStandardTaxId'),

            paymentMethodId:                          self::strOrNull($data, 'paymentMethodId'),
            termOfPaymentId:                          self::strOrNull($data, 'termOfPaymentId'),
            shipmentMethodId:                         self::strOrNull($data, 'shipmentMethodId'),
            shippingCarrierId:                        self::strOrNull($data, 'shippingCarrierId'),
            warehouseId:                              self::strOrNull($data, 'warehouseId'),
            salesOrderId:                             self::strOrNull($data, 'salesOrderId'),
            purchaseOrderRequestId:                   self::strOrNull($data, 'purchaseOrderRequestId'),
            mergedToPurchaseOrderId:                  self::strOrNull($data, 'mergedToPurchaseOrderId'),
            supplierHabitualExporterLetterOfIntentId: self::strOrNull($data, 'supplierHabitualExporterLetterOfIntentId'),

            packageTrackingNumber:                    self::strOrNull($data, 'packageTrackingNumber'),
            packageTrackingUrl:                       self::strOrNull($data, 'packageTrackingUrl'),
            recipientCountryCode:                     self::strOrNull($data, 'recipientCountryCode'),
            senderCountryCode:                        self::strOrNull($data, 'senderCountryCode'),

            orderDate:                                self::int($data, 'orderDate'),
            plannedDeliveryDate:                      self::intOrNull($data, 'plannedDeliveryDate'),
            plannedShippingDate:                      self::intOrNull($data, 'plannedShippingDate'),
            servicePeriodFrom:                        self::intOrNull($data, 'servicePeriodFrom'),
            servicePeriodTo:                          self::intOrNull($data, 'servicePeriodTo'),
            shippingNotificationDate:                 self::intOrNull($data, 'shippingNotificationDate'),

            disableRecordEmailingRule:                self::bool($data, 'disableRecordEmailingRule'),
            includeCashDiscountInValuationPrice:      self::bool($data, 'includeCashDiscountInValuationPrice'),
            invoiced:                                 self::bool($data, 'invoiced'),
            paid:                                     self::bool($data, 'paid'),
            received:                                 self::bool($data, 'received'),
            sentToRecipient:                          self::bool($data, 'sentToRecipient'),

            recordComment:                            self::strOrNull($data, 'recordComment'),
            recordFreeText:                           self::strOrNull($data, 'recordFreeText'),
            recordOpening:                            self::strOrNull($data, 'recordOpening'),

            deliveryAddress:                          $deliveryAddress,
            invoiceAddress:                           $invoiceAddress,
            recordAddress:                            $recordAddress,

            purchaseOrderItems:                       array_map(
                static fn(array $item) => PurchaseOrderItemDTO::fromArray($item),
                self::arr($data, 'purchaseOrderItems'),
            ),
            shippingCostItems:                        array_map(
                static fn(array $item) => ShippingCostItemDTO::fromArray($item),
                self::arr($data, 'shippingCostItems'),
            ),
            customAttributes:                         array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
            statusHistory:                            self::arr($data, 'statusHistory'),
            tags:                                     self::arr($data, 'tags'),
            dropshippingDeliveryNoteFormTexts:        self::arr($data, 'dropshippingDeliveryNoteFormTexts'),

            recordEmailAddresses:                     isset($data['recordEmailAddresses']) && is_array($data['recordEmailAddresses'])
                                                          ? EmailAddressesDTO::fromArray($data['recordEmailAddresses'])
                                                          : null,
        );
    }

    /**
     * Returns true if all ordered goods have been received.
     */
    public function isFullyReceived(): bool
    {
        return $this->received;
    }

    /**
     * Returns true if the purchase order has been fully invoiced.
     */
    public function isFullyInvoiced(): bool
    {
        return $this->invoiced;
    }

    /**
     * Returns the net amount as a float, or null if not set.
     */
    public function getNetAmount(): ?float
    {
        return $this->netAmount !== null ? (float) $this->netAmount : null;
    }

    /**
     * Returns the gross amount as a float, or null if not set.
     */
    public function getGrossAmount(): ?float
    {
        return $this->grossAmount !== null ? (float) $this->grossAmount : null;
    }

    /**
     * Returns the order date as a DateTimeImmutable object.
     */
    public function getOrderDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['orderDate' => $this->orderDate], 'orderDate');
    }

    /**
     * Returns the planned delivery date as a DateTimeImmutable object.
     */
    public function getPlannedDeliveryDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['plannedDeliveryDate' => $this->plannedDeliveryDate], 'plannedDeliveryDate');
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
