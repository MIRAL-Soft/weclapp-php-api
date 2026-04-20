<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Sales Invoice from the weclapp API.
 *
 * Sales invoices map to the /api/v2/salesInvoice endpoint.
 * PDF downloads are available via SalesInvoiceResource::getPdf().
 *
 * This DTO covers all invoice types, including cancellation invoices (credit notes).
 * Use the salesInvoiceType field to distinguish between types:
 *   - STANDARD_INVOICE → regular invoice (RE-number range)
 *   - CREDIT_NOTE      → cancellation invoice (CLX-number range)
 *
 * To fetch only credit notes use SalesInvoiceResource::findCreditNotes().
 *
 * @see \miralsoft\weclapp\api\Resource\SalesInvoiceResource
 * @see \miralsoft\weclapp\api\DTO\SalesInvoiceItemDTO
 * @see \miralsoft\weclapp\api\Enum\SalesInvoiceType
 * @see \miralsoft\weclapp\api\Enum\SalesInvoiceStatus
 */
final class SalesInvoiceDTO extends AbstractDTO
{
    /**
     * @param string      $id                                      Internal weclapp UUID (readOnly).
     * @param string      $version                                 Optimistic locking version string (readOnly).
     * @param int         $createdDate                             Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate                        Last modification timestamp in epoch milliseconds (readOnly).
     * @param string      $invoiceNumber                           Human-readable invoice number (e.g. "RE-10042" or "CLX-1061").
     * @param string      $status                                  Invoice status. See SalesInvoiceStatus enum.
     * @param string      $salesInvoiceType                        Invoice type. See SalesInvoiceType enum.
     * @param string      $customerId                              ID of the linked customer.
     * @param string|null $customerNumber                          Human-readable customer number.
     * @param string|null $partyId                                 ID of the underlying party record.
     * @param string|null $customerName                            Customer display name (denormalised).
     * @param int         $invoiceDate                             Invoice date in epoch milliseconds.
     * @param int|null    $dueDate                                 Payment due date in epoch milliseconds.
     * @param int|null    $bookingDate                             Accounting booking date in epoch milliseconds.
     * @param string|null $bookingText                             Accounting booking text.
     * @param string|null $paymentMethodId                         ID of the assigned payment method.
     * @param string|null $paymentStatus                           Payment status (e.g. "OPEN", "PAID").
     * @param bool        $paid                                    True if the invoice has been fully paid.
     * @param string|null $netAmount                               Net invoice amount as a decimal string.
     * @param string|null $grossAmount                             Gross invoice amount as a decimal string.
     * @param string|null $openAmount                              Remaining unpaid amount as a decimal string.
     * @param string|null $netAmountInCompanyCurrency              Net amount in company currency (readOnly).
     * @param string|null $grossAmountInCompanyCurrency            Gross amount in company currency (readOnly).
     * @param string|null $headerDiscount                          Header-level discount percentage.
     * @param string|null $headerSurcharge                         Header-level surcharge percentage.
     * @param string|null $currency                                Currency code (e.g. "EUR").
     * @param string|null $recordCurrencyId                        ID of the document currency.
     * @param string|null $currencyConversionRate                  Currency conversion rate as a decimal string.
     * @param int|null    $currencyConversionDate                  Date of currency conversion in epoch ms (readOnly).
     * @param bool        $currencyConversionLocked                True if the currency conversion rate is locked.
     * @param string|null $nonStandardTaxId                        ID of a non-standard tax rate.
     * @param string|null $salesOrderId                            ID of the originating sales order (if any).
     * @param string|null $precedingSalesInvoiceId                 For CREDIT_NOTE: ID of the original invoice being cancelled.
     * @param string|null $cancellationNumber                      For cancelled invoices: the CLX-number of the credit note.
     * @param int|null    $cancellationDate                        Date of cancellation in epoch milliseconds.
     * @param bool        $cancellationSlipCommissionBlock         True if commission is blocked on the cancellation slip.
     * @param bool        $cancellationSlipCommissionSettlementDone True if commission settlement is done (readOnly).
     * @param bool        $commissionBlock                         True if commission is blocked.
     * @param bool        $commissionSettlementDone                True if commission settlement is done (readOnly).
     * @param string|null $responsibleUserId                       ID of the responsible weclapp user.
     * @param string|null $creatorId                               ID of the user who created the invoice (readOnly).
     * @param string|null $salesChannel                            Assigned sales channel.
     * @param string|null $commission                              Commission note or identifier.
     * @param string|null $commercialLanguage                      Commercial language code.
     * @param string|null $costCenterId                            ID of the cost centre.
     * @param string|null $costTypeId                              ID of the cost type.
     * @param bool        $creditResetsOrderState                  True if the credit note resets the order state.
     * @param int|null    $deliveryDate                            Delivery date in epoch milliseconds.
     * @param bool        $directDebitFileCreated                  True if a direct debit file has been created.
     * @param int|null    $directDebitFileLatestDate               Latest date for direct debit file (readOnly).
     * @param bool        $disableRecordEmailingRule               True if the automatic email rule is disabled.
     * @param int|null    $dunningBlockDateUntilDate               Date until which dunning is blocked (readOnly).
     * @param string|null $dunningBlockNote                        Note explaining the dunning block (readOnly).
     * @param string|null $dunningBlockState                       State of the dunning block (readOnly).
     * @param string|null $epcQrCodeReference                      EPC QR code payment reference (readOnly).
     * @param bool        $factoring                               True if factoring is enabled.
     * @param string|null $customerHabitualExporterLetterOfIntentId ID of the habitual exporter letter.
     * @param string|null $orderNumberAtCustomer                   Order number at the customer side.
     * @param int|null    $pricingDate                             Pricing date in epoch milliseconds.
     * @param string|null $quotationId                             ID of the originating quotation (readOnly).
     * @param string|null $recordComment                           HTML comment on the record.
     * @param bool        $recordCommentInheritance                True if the comment is inherited.
     * @param string|null $recordFreeText                          HTML free text field on the record.
     * @param bool        $recordFreeTextInheritance               True if the free text is inherited.
     * @param string|null $recordOpening                           HTML opening text on the record.
     * @param bool        $recordOpeningInheritance                True if the opening is inherited.
     * @param bool        $sentToRecipient                         True if the document has been sent to the recipient.
     * @param string|null $sepaDirectDebitMandateId                ID of the SEPA direct debit mandate.
     * @param int|null    $servicePeriodFrom                       Service period start date in epoch milliseconds.
     * @param int|null    $servicePeriodTo                         Service period end date in epoch milliseconds.
     * @param string|null $shipmentMethodId                        ID of the shipment method.
     * @param int|null    $shippingDate                            Shipping date in epoch milliseconds.
     * @param string|null $termOfPaymentId                         ID of the term of payment.
     * @param string|null $vatRegistrationNumber                   VAT registration number.
     * @param string|null $collectiveInvoicePositionPrintType      Print type for collective invoice positions.
     * @param AddressDTO|null  $deliveryAddress                    Delivery address for this invoice.
     * @param AddressDTO|null  $recordAddress                      Record address for this invoice.
     * @param list<SalesInvoiceItemDTO>       $salesInvoiceItems        Line items of this invoice.
     * @param list<CommissionSalesPartnerDTO> $commissionSalesPartners  Commission assignments.
     * @param list<ShippingCostItemDTO>       $shippingCostItems        Shipping cost items.
     * @param list<StatusHistoryDTO>          $statusHistory            Status change history (readOnly).
     * @param list<CustomAttributeDTO>        $customAttributes         Custom attribute values.
     * @param list<array>                     $salesOrders              Linked sales order references [{id}].
     * @param list<array>                     $tags                     List of tag objects.
     * @param EmailAddressesDTO|null          $recordEmailAddresses     Record e-mail address overrides.
     * @param string|null                     $dispatchCountryCode      Country code used for dispatch (enum: country).
     */
    public function __construct(
        // Identity
        public readonly string      $id,
        public readonly string      $version,
        public readonly int         $createdDate,
        public readonly int         $lastModifiedDate,

        // Core fields
        public readonly string      $invoiceNumber,
        public readonly string      $status,
        public readonly string      $salesInvoiceType,
        public readonly string      $customerId,
        public readonly ?string     $customerNumber,
        public readonly ?string     $partyId,
        public readonly ?string     $customerName,
        public readonly int         $invoiceDate,
        public readonly ?int        $dueDate,
        public readonly ?int        $bookingDate,
        public readonly ?string     $bookingText,

        // Payment
        public readonly ?string     $paymentMethodId,
        public readonly ?string     $paymentStatus,
        public readonly bool        $paid,

        // Amounts
        public readonly ?string     $netAmount,
        public readonly ?string     $grossAmount,
        public readonly ?string     $openAmount,
        public readonly ?string     $netAmountInCompanyCurrency,
        public readonly ?string     $grossAmountInCompanyCurrency,
        public readonly ?string     $headerDiscount,
        public readonly ?string     $headerSurcharge,

        // Currency
        public readonly ?string     $currency,
        public readonly ?string     $recordCurrencyId,
        public readonly ?string     $currencyConversionRate,
        public readonly ?int        $currencyConversionDate,
        public readonly bool        $currencyConversionLocked,
        public readonly ?string     $nonStandardTaxId,

        // References
        public readonly ?string     $salesOrderId,
        public readonly ?string     $precedingSalesInvoiceId,
        public readonly ?string     $cancellationNumber,
        public readonly ?int        $cancellationDate,
        public readonly ?string     $quotationId,
        public readonly ?string     $orderNumberAtCustomer,

        // Cancellation/commission flags
        public readonly bool        $cancellationSlipCommissionBlock,
        public readonly bool        $cancellationSlipCommissionSettlementDone,
        public readonly bool        $commissionBlock,
        public readonly bool        $commissionSettlementDone,

        // User references
        public readonly ?string     $responsibleUserId,
        public readonly ?string     $creatorId,

        // Sales & commercial
        public readonly ?string     $salesChannel,
        public readonly ?string     $commission,
        public readonly ?string     $commercialLanguage,

        // Cost accounting
        public readonly ?string     $costCenterId,
        public readonly ?string     $costTypeId,

        // Dates
        public readonly ?int        $deliveryDate,
        public readonly ?int        $pricingDate,
        public readonly ?int        $servicePeriodFrom,
        public readonly ?int        $servicePeriodTo,
        public readonly ?int        $shippingDate,

        // Dunning (readOnly)
        public readonly ?int        $dunningBlockDateUntilDate,
        public readonly ?string     $dunningBlockNote,
        public readonly ?string     $dunningBlockState,

        // Flags
        public readonly bool        $creditResetsOrderState,
        public readonly bool        $directDebitFileCreated,
        public readonly ?int        $directDebitFileLatestDate,
        public readonly bool        $disableRecordEmailingRule,
        public readonly bool        $factoring,
        public readonly bool        $sentToRecipient,

        // Other readOnly
        public readonly ?string     $epcQrCodeReference,

        // Logistics / dispatch
        public readonly ?string     $customerHabitualExporterLetterOfIntentId,
        public readonly ?string     $dispatchCountryCode,
        public readonly ?string     $shipmentMethodId,
        public readonly ?string     $termOfPaymentId,
        public readonly ?string     $sepaDirectDebitMandateId,
        public readonly ?string     $vatRegistrationNumber,
        public readonly ?string     $collectiveInvoicePositionPrintType,

        // Record text fields
        public readonly ?string     $recordComment,
        public readonly bool        $recordCommentInheritance,
        public readonly ?string     $recordFreeText,
        public readonly bool        $recordFreeTextInheritance,
        public readonly ?string     $recordOpening,
        public readonly bool        $recordOpeningInheritance,

        // Addresses
        public readonly ?AddressDTO $deliveryAddress,
        public readonly ?AddressDTO $recordAddress,

        // Nested typed arrays
        public readonly array       $salesInvoiceItems,
        public readonly array       $commissionSalesPartners,
        public readonly array       $shippingCostItems,
        public readonly array       $statusHistory,
        public readonly array       $customAttributes,
        public readonly array       $salesOrders,
        public readonly array       $tags,

        // Typed email address object
        public readonly ?EmailAddressesDTO $recordEmailAddresses,
    ) {}

    /**
     * Create a SalesInvoiceDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $deliveryAddress = isset($data['deliveryAddress']) && is_array($data['deliveryAddress'])
            ? AddressDTO::fromArray($data['deliveryAddress'])
            : null;

        $recordAddress = isset($data['recordAddress']) && is_array($data['recordAddress'])
            ? AddressDTO::fromArray($data['recordAddress'])
            : null;

        return new static(
            id:                                       self::str($data, 'id'),
            version:                                  self::str($data, 'version'),
            createdDate:                              self::int($data, 'createdDate'),
            lastModifiedDate:                         self::int($data, 'lastModifiedDate'),

            invoiceNumber:                            self::str($data, 'invoiceNumber'),
            status:                                   self::str($data, 'status'),
            salesInvoiceType:                         self::str($data, 'salesInvoiceType'),
            customerId:                               self::str($data, 'customerId'),
            customerNumber:                           self::strOrNull($data, 'customerNumber'),
            partyId:                                  self::strOrNull($data, 'partyId'),
            customerName:                             self::strOrNull($data, 'customerName'),
            invoiceDate:                              self::int($data, 'invoiceDate'),
            dueDate:                                  self::intOrNull($data, 'dueDate'),
            bookingDate:                              self::intOrNull($data, 'bookingDate'),
            bookingText:                              self::strOrNull($data, 'bookingText'),

            paymentMethodId:                          self::strOrNull($data, 'paymentMethodId'),
            paymentStatus:                            self::strOrNull($data, 'paymentStatus'),
            paid:                                     self::bool($data, 'paid'),

            netAmount:                                self::strOrNull($data, 'netAmount'),
            grossAmount:                              self::strOrNull($data, 'grossAmount'),
            openAmount:                               self::strOrNull($data, 'openAmount'),
            netAmountInCompanyCurrency:               self::strOrNull($data, 'netAmountInCompanyCurrency'),
            grossAmountInCompanyCurrency:             self::strOrNull($data, 'grossAmountInCompanyCurrency'),
            headerDiscount:                           self::strOrNull($data, 'headerDiscount'),
            headerSurcharge:                          self::strOrNull($data, 'headerSurcharge'),

            currency:                                 self::strOrNull($data, 'currency'),
            recordCurrencyId:                         self::strOrNull($data, 'recordCurrencyId'),
            currencyConversionRate:                   self::strOrNull($data, 'currencyConversionRate'),
            currencyConversionDate:                   self::intOrNull($data, 'currencyConversionDate'),
            currencyConversionLocked:                 self::bool($data, 'currencyConversionLocked'),
            nonStandardTaxId:                         self::strOrNull($data, 'nonStandardTaxId'),

            salesOrderId:                             self::strOrNull($data, 'salesOrderId'),
            precedingSalesInvoiceId:                  self::strOrNull($data, 'precedingSalesInvoiceId'),
            cancellationNumber:                       self::strOrNull($data, 'cancellationNumber'),
            cancellationDate:                         self::intOrNull($data, 'cancellationDate'),
            quotationId:                              self::strOrNull($data, 'quotationId'),
            orderNumberAtCustomer:                    self::strOrNull($data, 'orderNumberAtCustomer'),

            cancellationSlipCommissionBlock:          self::bool($data, 'cancellationSlipCommissionBlock'),
            cancellationSlipCommissionSettlementDone: self::bool($data, 'cancellationSlipCommissionSettlementDone'),
            commissionBlock:                          self::bool($data, 'commissionBlock'),
            commissionSettlementDone:                 self::bool($data, 'commissionSettlementDone'),

            responsibleUserId:                        self::strOrNull($data, 'responsibleUserId'),
            creatorId:                                self::strOrNull($data, 'creatorId'),

            salesChannel:                             self::strOrNull($data, 'salesChannel'),
            commission:                               self::strOrNull($data, 'commission'),
            commercialLanguage:                       self::strOrNull($data, 'commercialLanguage'),

            costCenterId:                             self::strOrNull($data, 'costCenterId'),
            costTypeId:                               self::strOrNull($data, 'costTypeId'),

            deliveryDate:                             self::intOrNull($data, 'deliveryDate'),
            pricingDate:                              self::intOrNull($data, 'pricingDate'),
            servicePeriodFrom:                        self::intOrNull($data, 'servicePeriodFrom'),
            servicePeriodTo:                          self::intOrNull($data, 'servicePeriodTo'),
            shippingDate:                             self::intOrNull($data, 'shippingDate'),

            dunningBlockDateUntilDate:                self::intOrNull($data, 'dunningBlockDateUntilDate'),
            dunningBlockNote:                         self::strOrNull($data, 'dunningBlockNote'),
            dunningBlockState:                        self::strOrNull($data, 'dunningBlockState'),

            creditResetsOrderState:                   self::bool($data, 'creditResetsOrderState'),
            directDebitFileCreated:                   self::bool($data, 'directDebitFileCreated'),
            directDebitFileLatestDate:                self::intOrNull($data, 'directDebitFileLatestDate'),
            disableRecordEmailingRule:                self::bool($data, 'disableRecordEmailingRule'),
            factoring:                                self::bool($data, 'factoring'),
            sentToRecipient:                          self::bool($data, 'sentToRecipient'),

            epcQrCodeReference:                       self::strOrNull($data, 'epcQrCodeReference'),

            customerHabitualExporterLetterOfIntentId: self::strOrNull($data, 'customerHabitualExporterLetterOfIntentId'),
            dispatchCountryCode:                      self::strOrNull($data, 'dispatchCountryCode'),
            shipmentMethodId:                         self::strOrNull($data, 'shipmentMethodId'),
            termOfPaymentId:                          self::strOrNull($data, 'termOfPaymentId'),
            sepaDirectDebitMandateId:                 self::strOrNull($data, 'sepaDirectDebitMandateId'),
            vatRegistrationNumber:                    self::strOrNull($data, 'vatRegistrationNumber'),
            collectiveInvoicePositionPrintType:       self::strOrNull($data, 'collectiveInvoicePositionPrintType'),

            recordComment:                            self::strOrNull($data, 'recordComment'),
            recordCommentInheritance:                 self::bool($data, 'recordCommentInheritance'),
            recordFreeText:                           self::strOrNull($data, 'recordFreeText'),
            recordFreeTextInheritance:                self::bool($data, 'recordFreeTextInheritance'),
            recordOpening:                            self::strOrNull($data, 'recordOpening'),
            recordOpeningInheritance:                 self::bool($data, 'recordOpeningInheritance'),

            deliveryAddress:                          $deliveryAddress,
            recordAddress:                            $recordAddress,

            salesInvoiceItems:                        array_map(
                static fn(array $item) => SalesInvoiceItemDTO::fromArray($item),
                self::arr($data, 'salesInvoiceItems'),
            ),
            commissionSalesPartners:                  array_map(
                static fn(array $item) => CommissionSalesPartnerDTO::fromArray($item),
                self::arr($data, 'commissionSalesPartners'),
            ),
            shippingCostItems:                        array_map(
                static fn(array $item) => ShippingCostItemDTO::fromArray($item),
                self::arr($data, 'shippingCostItems'),
            ),
            statusHistory:                            array_map(
                static fn(array $item) => StatusHistoryDTO::fromArray($item),
                self::arr($data, 'statusHistory'),
            ),
            customAttributes:                         array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
            salesOrders:                              self::arr($data, 'salesOrders'),
            tags:                                     self::arr($data, 'tags'),

            recordEmailAddresses:                     isset($data['recordEmailAddresses']) && is_array($data['recordEmailAddresses'])
                                                          ? EmailAddressesDTO::fromArray($data['recordEmailAddresses'])
                                                          : null,
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
     * Returns the invoice date as a DateTimeImmutable object.
     */
    public function getInvoiceDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['invoiceDate' => $this->invoiceDate], 'invoiceDate');
    }

    /**
     * Returns the due date as a DateTimeImmutable object.
     */
    public function getDueDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['dueDate' => $this->dueDate], 'dueDate');
    }

    /**
     * Returns the accounting booking date as a DateTimeImmutable object.
     */
    public function getBookingDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['bookingDate' => $this->bookingDate], 'bookingDate');
    }

    /**
     * Returns the delivery date as a DateTimeImmutable object.
     */
    public function getDeliveryDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['deliveryDate' => $this->deliveryDate], 'deliveryDate');
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
     * Returns the open (unpaid) amount as a float, or null if not set.
     */
    public function getOpenAmount(): ?float
    {
        return $this->openAmount !== null ? (float) $this->openAmount : null;
    }

    /**
     * Returns true if this invoice is a cancellation invoice (credit note).
     *
     * Cancellation invoices carry a CLX-prefixed invoiceNumber and have their
     * precedingSalesInvoiceId set to the ID of the original invoice.
     */
    public function isCreditNote(): bool
    {
        return $this->salesInvoiceType === 'CREDIT_NOTE';
    }

    /**
     * Returns the best available customer display name from inline invoice data.
     *
     * Uses the denormalised customerName field if the API returned it.
     * Falls back to the customerNumber, then to 'Unknown'.
     */
    public function getCustomerDisplayName(): string
    {
        return $this->customerName
            ?? $this->customerNumber
            ?? 'Unknown';
    }

    /**
     * Returns true if the invoice has an outstanding open amount.
     */
    public function isOpen(): bool
    {
        return ($this->getOpenAmount() ?? 0.0) > 0.0;
    }
}
