<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Quotation (offer/Angebot) from the weclapp API.
 *
 * Quotations map to the /api/v2/quotation endpoint.
 * A quotation can be converted to a SalesOrder via QuotationResource::convertToSalesOrder().
 *
 * NOTE: The API uses `validFrom` / `validTo` (not `validUntilDate`).
 * The former `validUntilDate` field has been corrected to `validTo`.
 *
 * @see \miralsoft\weclapp\api\Resource\QuotationResource
 */
final class QuotationDTO extends AbstractDTO
{
    /**
     * @param string      $id                             Internal weclapp UUID (readOnly).
     * @param string      $version                        Optimistic locking version string (readOnly).
     * @param int         $createdDate                    Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate               Last modification timestamp in epoch milliseconds (readOnly).
     * @param string      $quotationNumber                Human-readable quotation number (e.g. "ANG-10042").
     * @param string|null $status                         Quotation status (e.g. "QUOTATION_DRAFT", "QUOTATION_SENT").
     * @param string      $customerId                     ID of the linked customer.
     * @param string|null $customerName                   Customer display name (denormalised).
     * @param int         $quotationDate                  Quotation date in epoch milliseconds.
     * @param string|null $description                    Internal description / comment.
     * @param string|null $responsibleUserId              ID of the responsible weclapp user.
     * @param string|null $creatorId                      ID of the user who created the quotation (readOnly).
     * @param bool        $activeVersion                  True if this is the active version of the quotation.
     * @param int         $quotationVersion               Version number of the quotation (readOnly).
     * @param string|null $quotationType                  Quotation type identifier.
     * @param string|null $salesChannel                   Assigned sales channel.
     * @param string|null $commission                     Commission note or identifier.
     * @param string|null $commercialLanguage             Commercial language code.
     * @param string|null $currencyConversionRate         Currency conversion rate as a decimal string.
     * @param int|null    $currencyConversionDate         Date of currency conversion in epoch ms (readOnly).
     * @param bool        $currencyConversionLocked       True if the currency conversion rate is locked.
     * @param string|null $recordCurrencyId               ID of the document currency.
     * @param string|null $netAmount                      Net quotation amount as a decimal string.
     * @param string|null $grossAmount                    Gross quotation amount as a decimal string.
     * @param string|null $netAmountInCompanyCurrency     Net amount in company currency (readOnly).
     * @param string|null $grossAmountInCompanyCurrency   Gross amount in company currency (readOnly).
     * @param string|null $headerDiscount                 Header-level discount percentage.
     * @param string|null $headerSurcharge                Header-level surcharge percentage.
     * @param string|null $nonStandardTaxId               ID of a non-standard tax rate.
     * @param string|null $currency                       Currency code (e.g. "EUR").
     * @param string|null $mergedToQuotationId            ID of the quotation this was merged into (readOnly).
     * @param string|null $opportunityId                  ID of the linked opportunity.
     * @param string|null $salesStageId                   ID of the current sales stage.
     * @param int         $salesProbability               Sales probability as a percentage (0-100).
     * @param int|null    $expectedSignatureDate          Expected signature date in epoch milliseconds.
     * @param int|null    $requestDate                    Date the quotation was requested in epoch milliseconds.
     * @param int|null    $validFrom                      Validity start date in epoch milliseconds.
     * @param int|null    $validTo                        Validity end date in epoch milliseconds.
     * @param int|null    $plannedDeliveryDate            Planned delivery date in epoch milliseconds.
     * @param int|null    $plannedShippingDate            Planned shipping date in epoch milliseconds.
     * @param int|null    $pricingDate                    Pricing date in epoch milliseconds.
     * @param int|null    $servicePeriodFrom              Service period start date in epoch milliseconds.
     * @param int|null    $servicePeriodTo                Service period end date in epoch milliseconds.
     * @param bool        $disableRecordEmailingRule      True if the automatic email rule is disabled.
     * @param bool        $factoring                      True if factoring is enabled.
     * @param bool        $sentToRecipient                True if the document has been sent to the recipient.
     * @param bool        $template                       True if this is a template quotation.
     * @param string|null $invoiceRecipientId             ID of the invoice recipient.
     * @param string|null $defaultShippingCarrierId       ID of the default shipping carrier.
     * @param string|null $paymentMethodId                ID of the payment method.
     * @param string|null $shipmentMethodId               ID of the shipment method.
     * @param string|null $termOfPaymentId                ID of the term of payment.
     * @param string|null $warehouseId                    ID of the warehouse.
     * @param string|null $rejectionReason                Reason for rejection.
     * @param string|null $publicLink                     Public link for the quotation.
     * @param string|null $recordComment                  HTML comment on the record.
     * @param bool        $recordCommentInheritance       True if the comment is inherited.
     * @param string|null $recordFreeText                 HTML free text field on the record.
     * @param bool        $recordFreeTextInheritance      True if the free text is inherited.
     * @param string|null $recordOpening                  HTML opening text on the record.
     * @param bool        $recordOpeningInheritance       True if the opening is inherited.
     * @param AddressDTO|null  $deliveryAddress           Delivery address for this quotation.
     * @param AddressDTO|null  $invoiceAddress            Invoice address for this quotation.
     * @param AddressDTO|null  $recordAddress             Record address for this quotation.
     * @param list<QuotationItemDTO>          $quotationItems          Line items of this quotation.
     * @param list<CommissionSalesPartnerDTO> $commissionSalesPartners Commission assignments.
     * @param list<ShippingCostItemDTO>       $shippingCostItems       Shipping cost items.
     * @param list<StatusHistoryDTO>          $statusHistory           Status change history (readOnly).
     * @param list<CustomAttributeDTO>        $customAttributes        Custom attribute values.
     * @param list<array>                     $salesStageHistory       Sales stage change history (readOnly, raw).
     * @param list<array>                     $tags                    List of tag objects.
     */
    public function __construct(
        // Identity
        public readonly string      $id,
        public readonly string      $version,
        public readonly int         $createdDate,
        public readonly int         $lastModifiedDate,

        // Core fields
        public readonly string      $quotationNumber,
        public readonly ?string     $status,
        public readonly string      $customerId,
        public readonly ?string     $customerName,
        public readonly int         $quotationDate,
        public readonly ?string     $description,
        public readonly ?string     $responsibleUserId,
        public readonly ?string     $creatorId,

        // Versioning
        public readonly bool        $activeVersion,
        public readonly int         $quotationVersion,
        public readonly ?string     $quotationType,

        // Sales & commercial
        public readonly ?string     $salesChannel,
        public readonly ?string     $commission,
        public readonly ?string     $commercialLanguage,

        // Currency
        public readonly ?string     $currencyConversionRate,
        public readonly ?int        $currencyConversionDate,
        public readonly bool        $currencyConversionLocked,
        public readonly ?string     $recordCurrencyId,

        // Amounts
        public readonly ?string     $netAmount,
        public readonly ?string     $grossAmount,
        public readonly ?string     $netAmountInCompanyCurrency,
        public readonly ?string     $grossAmountInCompanyCurrency,
        public readonly ?string     $headerDiscount,
        public readonly ?string     $headerSurcharge,
        public readonly ?string     $nonStandardTaxId,
        public readonly ?string     $currency,

        // CRM linkage
        public readonly ?string     $mergedToQuotationId,
        public readonly ?string     $opportunityId,
        public readonly ?string     $salesStageId,
        public readonly int         $salesProbability,

        // Dates
        public readonly ?int        $expectedSignatureDate,
        public readonly ?int        $requestDate,
        public readonly ?int        $validFrom,
        public readonly ?int        $validTo,
        public readonly ?int        $plannedDeliveryDate,
        public readonly ?int        $plannedShippingDate,
        public readonly ?int        $pricingDate,
        public readonly ?int        $servicePeriodFrom,
        public readonly ?int        $servicePeriodTo,

        // Flags
        public readonly bool        $disableRecordEmailingRule,
        public readonly bool        $factoring,
        public readonly bool        $sentToRecipient,
        public readonly bool        $template,

        // References
        public readonly ?string     $invoiceRecipientId,
        public readonly ?string     $defaultShippingCarrierId,
        public readonly ?string     $paymentMethodId,
        public readonly ?string     $shipmentMethodId,
        public readonly ?string     $termOfPaymentId,
        public readonly ?string     $warehouseId,

        // Other
        public readonly ?string     $rejectionReason,
        public readonly ?string     $publicLink,

        // Record text fields
        public readonly ?string     $recordComment,
        public readonly bool        $recordCommentInheritance,
        public readonly ?string     $recordFreeText,
        public readonly bool        $recordFreeTextInheritance,
        public readonly ?string     $recordOpening,
        public readonly bool        $recordOpeningInheritance,

        // Addresses
        public readonly ?AddressDTO $deliveryAddress,
        public readonly ?AddressDTO $invoiceAddress,
        public readonly ?AddressDTO $recordAddress,

        // Nested typed arrays
        public readonly array       $quotationItems,
        public readonly array       $commissionSalesPartners,
        public readonly array       $shippingCostItems,
        public readonly array       $statusHistory,
        public readonly array       $customAttributes,
        public readonly array       $salesStageHistory,
        public readonly array       $tags,
    ) {}

    /**
     * Create a QuotationDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $deliveryAddress = isset($data['deliveryAddress']) && is_array($data['deliveryAddress'])
            ? AddressDTO::fromArray($data['deliveryAddress'])
            : null;

        $invoiceAddress = isset($data['invoiceAddress']) && is_array($data['invoiceAddress'])
            ? AddressDTO::fromArray($data['invoiceAddress'])
            : null;

        $recordAddress = isset($data['recordAddress']) && is_array($data['recordAddress'])
            ? AddressDTO::fromArray($data['recordAddress'])
            : null;

        return new static(
            id:                          self::str($data, 'id'),
            version:                     self::str($data, 'version'),
            createdDate:                 self::int($data, 'createdDate'),
            lastModifiedDate:            self::int($data, 'lastModifiedDate'),

            quotationNumber:             self::str($data, 'quotationNumber'),
            status:                      self::strOrNull($data, 'status'),
            customerId:                  self::str($data, 'customerId'),
            customerName:                self::strOrNull($data, 'customerName'),
            quotationDate:               self::int($data, 'quotationDate'),
            description:                 self::strOrNull($data, 'description'),
            responsibleUserId:           self::strOrNull($data, 'responsibleUserId'),
            creatorId:                   self::strOrNull($data, 'creatorId'),

            activeVersion:               self::bool($data, 'activeVersion'),
            quotationVersion:            self::int($data, 'quotationVersion'),
            quotationType:               self::strOrNull($data, 'quotationType'),

            salesChannel:                self::strOrNull($data, 'salesChannel'),
            commission:                  self::strOrNull($data, 'commission'),
            commercialLanguage:          self::strOrNull($data, 'commercialLanguage'),

            currencyConversionRate:      self::strOrNull($data, 'currencyConversionRate'),
            currencyConversionDate:      self::intOrNull($data, 'currencyConversionDate'),
            currencyConversionLocked:    self::bool($data, 'currencyConversionLocked'),
            recordCurrencyId:            self::strOrNull($data, 'recordCurrencyId'),

            netAmount:                   self::strOrNull($data, 'netAmount'),
            grossAmount:                 self::strOrNull($data, 'grossAmount'),
            netAmountInCompanyCurrency:  self::strOrNull($data, 'netAmountInCompanyCurrency'),
            grossAmountInCompanyCurrency: self::strOrNull($data, 'grossAmountInCompanyCurrency'),
            headerDiscount:              self::strOrNull($data, 'headerDiscount'),
            headerSurcharge:             self::strOrNull($data, 'headerSurcharge'),
            nonStandardTaxId:            self::strOrNull($data, 'nonStandardTaxId'),
            currency:                    self::strOrNull($data, 'currency'),

            mergedToQuotationId:         self::strOrNull($data, 'mergedToQuotationId'),
            opportunityId:               self::strOrNull($data, 'opportunityId'),
            salesStageId:                self::strOrNull($data, 'salesStageId'),
            salesProbability:            self::int($data, 'salesProbability'),

            expectedSignatureDate:       self::intOrNull($data, 'expectedSignatureDate'),
            requestDate:                 self::intOrNull($data, 'requestDate'),
            validFrom:                   self::intOrNull($data, 'validFrom'),
            validTo:                     self::intOrNull($data, 'validTo'),
            plannedDeliveryDate:         self::intOrNull($data, 'plannedDeliveryDate'),
            plannedShippingDate:         self::intOrNull($data, 'plannedShippingDate'),
            pricingDate:                 self::intOrNull($data, 'pricingDate'),
            servicePeriodFrom:           self::intOrNull($data, 'servicePeriodFrom'),
            servicePeriodTo:             self::intOrNull($data, 'servicePeriodTo'),

            disableRecordEmailingRule:   self::bool($data, 'disableRecordEmailingRule'),
            factoring:                   self::bool($data, 'factoring'),
            sentToRecipient:             self::bool($data, 'sentToRecipient'),
            template:                    self::bool($data, 'template'),

            invoiceRecipientId:          self::strOrNull($data, 'invoiceRecipientId'),
            defaultShippingCarrierId:    self::strOrNull($data, 'defaultShippingCarrierId'),
            paymentMethodId:             self::strOrNull($data, 'paymentMethodId'),
            shipmentMethodId:            self::strOrNull($data, 'shipmentMethodId'),
            termOfPaymentId:             self::strOrNull($data, 'termOfPaymentId'),
            warehouseId:                 self::strOrNull($data, 'warehouseId'),

            rejectionReason:             self::strOrNull($data, 'rejectionReason'),
            publicLink:                  self::strOrNull($data, 'publicLink'),

            recordComment:               self::strOrNull($data, 'recordComment'),
            recordCommentInheritance:    self::bool($data, 'recordCommentInheritance'),
            recordFreeText:              self::strOrNull($data, 'recordFreeText'),
            recordFreeTextInheritance:   self::bool($data, 'recordFreeTextInheritance'),
            recordOpening:               self::strOrNull($data, 'recordOpening'),
            recordOpeningInheritance:    self::bool($data, 'recordOpeningInheritance'),

            deliveryAddress:             $deliveryAddress,
            invoiceAddress:              $invoiceAddress,
            recordAddress:               $recordAddress,

            quotationItems:              array_map(
                static fn(array $item) => QuotationItemDTO::fromArray($item),
                self::arr($data, 'quotationItems'),
            ),
            commissionSalesPartners:     array_map(
                static fn(array $item) => CommissionSalesPartnerDTO::fromArray($item),
                self::arr($data, 'commissionSalesPartners'),
            ),
            shippingCostItems:           array_map(
                static fn(array $item) => ShippingCostItemDTO::fromArray($item),
                self::arr($data, 'shippingCostItems'),
            ),
            statusHistory:               array_map(
                static fn(array $item) => StatusHistoryDTO::fromArray($item),
                self::arr($data, 'statusHistory'),
            ),
            customAttributes:            array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
            salesStageHistory:           self::arr($data, 'salesStageHistory'),
            tags:                        self::arr($data, 'tags'),
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
     * Returns the quotation date as a DateTimeImmutable object.
     */
    public function getQuotationDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['quotationDate' => $this->quotationDate], 'quotationDate');
    }

    /**
     * Returns the validity start date as a DateTimeImmutable object, or null if not set.
     */
    public function getValidFrom(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['validFrom' => $this->validFrom], 'validFrom');
    }

    /**
     * Returns the validity end date as a DateTimeImmutable object, or null if not set.
     */
    public function getValidTo(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['validTo' => $this->validTo], 'validTo');
    }

    /**
     * Returns the planned delivery date as a DateTimeImmutable object.
     */
    public function getPlannedDeliveryDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['plannedDeliveryDate' => $this->plannedDeliveryDate], 'plannedDeliveryDate');
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
     * Returns true if the quotation has expired (validTo date is in the past).
     *
     * Returns false if no expiry date is set.
     */
    public function isExpired(): bool
    {
        $validTo = $this->getValidTo();

        return $validTo !== null && $validTo < new DateTimeImmutable();
    }
}
