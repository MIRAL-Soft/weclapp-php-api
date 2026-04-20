<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Sales Order from the weclapp API.
 *
 * Sales orders map to the /api/v2/salesOrder endpoint.
 * PDF downloads are available via SalesOrderResource::getPdf().
 *
 * @see \miralsoft\weclapp\api\Resource\SalesOrderResource
 * @see \miralsoft\weclapp\api\DTO\SalesOrderItemDTO
 */
final class SalesOrderDTO extends AbstractDTO
{
    /**
     * @param string      $id                                    Internal weclapp UUID (readOnly).
     * @param string      $version                               Optimistic locking version string (readOnly).
     * @param int         $createdDate                           Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate                      Last modification timestamp in epoch milliseconds (readOnly).
     * @param string      $orderNumber                           Human-readable order number (e.g. "SO-10042").
     * @param string|null $status                                Order status (readOnly).
     * @param string|null $advancePaymentStatus                  Advance payment status (readOnly).
     * @param string      $customerId                            ID of the linked customer.
     * @param int         $orderDate                             Order date in epoch milliseconds.
     * @param string|null $description                           Internal description / comment.
     * @param string|null $responsibleUserId                     ID of the responsible weclapp user.
     * @param string|null $creatorId                             ID of the user who created the order (readOnly).
     * @param string|null $salesChannel                          Assigned sales channel.
     * @param string|null $commission                            Commission note or identifier.
     * @param string|null $commercialLanguage                    Commercial language code.
     * @param string|null $currencyConversionDate                Date of currency conversion in epoch ms (readOnly).
     * @param bool        $currencyConversionLocked              True if the currency conversion rate is locked.
     * @param string|null $currencyConversionRate                Currency conversion rate as a decimal string.
     * @param string|null $recordCurrencyId                      ID of the document currency.
     * @param string|null $netAmount                             Net order amount as a decimal string.
     * @param string|null $grossAmount                           Gross order amount as a decimal string.
     * @param string|null $netAmountInCompanyCurrency            Net amount in company currency (readOnly).
     * @param string|null $grossAmountInCompanyCurrency          Gross amount in company currency (readOnly).
     * @param string|null $headerDiscount                        Header-level discount percentage.
     * @param string|null $headerSurcharge                       Header-level surcharge percentage.
     * @param string|null $nonStandardTaxId                      ID of a non-standard tax rate.
     * @param string|null $quotationId                           ID of the originating quotation.
     * @param string|null $advancePaymentAmount                  Advance payment amount as a decimal string.
     * @param bool        $applyShippingCostsOnlyOnce            True if shipping costs should only apply once.
     * @param string|null $cashAccountId                         ID of the cash account.
     * @param string|null $customerHabitualExporterLetterOfIntentId ID of the habitual exporter letter.
     * @param string|null $defaultShippingCarrierId              ID of the default shipping carrier.
     * @param string|null $defaultShippingReturnCarrierId        ID of the default return shipping carrier.
     * @param bool        $disableRecordEmailingRule             True if the automatic email rule is disabled.
     * @param bool        $factoring                             True if factoring is enabled.
     * @param string|null $fulfillmentProviderId                 ID of the fulfillment provider.
     * @param string|null $invoiceRecipientId                    ID of the invoice recipient.
     * @param bool        $invoiced                              True if the order has been fully invoiced (readOnly).
     * @param bool        $paid                                  True if the order has been paid (readOnly).
     * @param bool        $onlyServices                          True if the order contains only service items.
     * @param string|null $orderNumberAtCustomer                 Order number at the customer side.
     * @param string|null $paymentMethodId                       ID of the payment method.
     * @param string|null $termOfPaymentId                       ID of the term of payment.
     * @param string|null $salesOrderPaymentType                 Payment type for the sales order.
     * @param string|null $sepaDirectDebitMandateId              ID of the SEPA direct debit mandate.
     * @param string|null $shipmentMethodId                      ID of the shipment method.
     * @param string|null $warehouseId                           ID of the warehouse.
     * @param int|null    $plannedDeliveryDate                   Planned delivery date in epoch milliseconds.
     * @param int|null    $plannedProjectEndDate                 Planned project end date in epoch milliseconds.
     * @param int|null    $plannedProjectStartDate               Planned project start date in epoch milliseconds.
     * @param int|null    $plannedShippingDate                   Planned shipping date in epoch milliseconds.
     * @param int|null    $pricingDate                           Pricing date in epoch milliseconds.
     * @param int|null    $servicePeriodFrom                     Service period start date in epoch milliseconds.
     * @param int|null    $servicePeriodTo                       Service period end date in epoch milliseconds.
     * @param bool        $shipped                               True if the order has been fully shipped (readOnly).
     * @param int         $shippingLabelsCount                   Number of shipping labels generated.
     * @param bool        $servicesFinished                      True if all services have been completed (readOnly).
     * @param bool        $sentToRecipient                       True if the order document has been sent to the recipient.
     * @param bool        $template                              True if this is a template order.
     * @param bool        $projectModeActive                     True if project mode is active.
     * @param string|null $projectGoals                          Project goals description.
     * @param string|null $recordAsn                             Advanced shipping notice record reference.
     * @param string|null $recordComment                         HTML comment on the record.
     * @param bool        $recordCommentInheritance              True if the comment is inherited.
     * @param string|null $recordFreeText                        HTML free text field on the record.
     * @param bool        $recordFreeTextInheritance             True if the free text is inherited.
     * @param string|null $recordOpening                         HTML opening text on the record.
     * @param bool        $recordOpeningInheritance              True if the opening is inherited.
     * @param string|null $note                                  Internal note.
     * @param string|null $dispatchCountryCode                   Country code used for dispatch (enum: country).
     * @param RecordAddressDTO|null $deliveryAddress              Delivery address for this order.
     * @param RecordAddressDTO|null $invoiceAddress              Invoice address for this order.
     * @param RecordAddressDTO|null $recordAddress               Record address for this order.
     * @param list<SalesOrderItemDTO>         $orderItems            Line items of this order.
     * @param list<CommissionSalesPartnerDTO> $commissionSalesPartners Commission assignments.
     * @param list<SalesOrderPaymentDTO>      $payments              Payment conditions / instalments.
     * @param list<ShippingCostItemDTO>       $shippingCostItems     Shipping cost items.
     * @param list<StatusHistoryDTO>          $statusHistory         Status change history (readOnly).
     * @param list<CustomAttributeDTO>        $customAttributes      Custom attribute values.
     * @param list<array>                     $tags                  List of tag objects.
     * @param EmailAddressesDTO|null          $deliveryEmailAddresses Delivery e-mail address overrides.
     * @param EmailAddressesDTO|null          $recordEmailAddresses   Record e-mail address overrides.
     * @param EmailAddressesDTO|null          $salesInvoiceEmailAddresses Sales invoice e-mail address overrides.
     * @param EcommerceOrderDTO|null          $ecommerceOrder        Linked e-commerce order metadata.
     * @param list<array>                     $projectMembers        Project members (raw).
     */
    public function __construct(
        // Identity
        public readonly string      $id,
        public readonly string      $version,
        public readonly int         $createdDate,
        public readonly int         $lastModifiedDate,

        // Core fields
        public readonly string      $orderNumber,
        public readonly ?string     $status,
        public readonly ?string     $advancePaymentStatus,
        public readonly string      $customerId,
        public readonly int         $orderDate,
        public readonly ?string     $description,
        public readonly ?string     $responsibleUserId,
        public readonly ?string     $creatorId,

        // Sales & commercial
        public readonly ?string     $salesChannel,
        public readonly ?string     $commission,
        public readonly ?string     $commercialLanguage,

        // Currency
        public readonly ?int        $currencyConversionDate,
        public readonly bool        $currencyConversionLocked,
        public readonly ?string     $currencyConversionRate,
        public readonly ?string     $recordCurrencyId,

        // Amounts
        public readonly ?string     $netAmount,
        public readonly ?string     $grossAmount,
        public readonly ?string     $netAmountInCompanyCurrency,
        public readonly ?string     $grossAmountInCompanyCurrency,
        public readonly ?string     $headerDiscount,
        public readonly ?string     $headerSurcharge,
        public readonly ?string     $nonStandardTaxId,

        // References
        public readonly ?string     $quotationId,
        public readonly ?string     $invoiceRecipientId,
        public readonly ?string     $orderNumberAtCustomer,

        // Advance payment
        public readonly ?string     $advancePaymentAmount,
        public readonly bool        $applyShippingCostsOnlyOnce,

        // Accounts & payment
        public readonly ?string     $cashAccountId,
        public readonly ?string     $paymentMethodId,
        public readonly ?string     $termOfPaymentId,
        public readonly ?string     $salesOrderPaymentType,
        public readonly ?string     $sepaDirectDebitMandateId,

        // Shipping & logistics
        public readonly ?string     $customerHabitualExporterLetterOfIntentId,
        public readonly ?string     $defaultShippingCarrierId,
        public readonly ?string     $defaultShippingReturnCarrierId,
        public readonly ?string     $fulfillmentProviderId,
        public readonly ?string     $shipmentMethodId,
        public readonly ?string     $warehouseId,

        // Dates
        public readonly ?int        $plannedDeliveryDate,
        public readonly ?int        $plannedProjectEndDate,
        public readonly ?int        $plannedProjectStartDate,
        public readonly ?int        $plannedShippingDate,
        public readonly ?int        $pricingDate,
        public readonly ?int        $servicePeriodFrom,
        public readonly ?int        $servicePeriodTo,

        // Status flags (readOnly)
        public readonly bool        $invoiced,
        public readonly bool        $paid,
        public readonly bool        $shipped,
        public readonly bool        $servicesFinished,

        // Flags
        public readonly bool        $onlyServices,
        public readonly bool        $disableRecordEmailingRule,
        public readonly bool        $factoring,
        public readonly bool        $sentToRecipient,
        public readonly bool        $template,
        public readonly bool        $projectModeActive,
        public readonly int         $shippingLabelsCount,

        // Project / record text
        public readonly ?string     $projectGoals,
        public readonly ?string     $recordAsn,
        public readonly ?string     $recordComment,
        public readonly bool        $recordCommentInheritance,
        public readonly ?string     $recordFreeText,
        public readonly bool        $recordFreeTextInheritance,
        public readonly ?string     $recordOpening,
        public readonly bool        $recordOpeningInheritance,
        public readonly ?string     $note,
        public readonly ?string     $dispatchCountryCode,

        // Addresses
        public readonly ?RecordAddressDTO $deliveryAddress,
        public readonly ?RecordAddressDTO $invoiceAddress,
        public readonly ?RecordAddressDTO $recordAddress,

        // Nested typed arrays
        public readonly array       $orderItems,
        public readonly array       $commissionSalesPartners,
        public readonly array       $payments,
        public readonly array       $shippingCostItems,
        public readonly array       $statusHistory,
        public readonly array       $customAttributes,
        public readonly array       $tags,

        // Typed email address objects
        public readonly ?EmailAddressesDTO  $deliveryEmailAddresses,
        public readonly ?EmailAddressesDTO  $recordEmailAddresses,
        public readonly ?EmailAddressesDTO  $salesInvoiceEmailAddresses,

        // Typed e-commerce metadata and raw arrays
        public readonly ?EcommerceOrderDTO  $ecommerceOrder,
        public readonly array               $projectMembers,
    ) {}

    /**
     * Create a SalesOrderDTO from a raw weclapp API response array.
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
            id:                                      self::str($data, 'id'),
            version:                                 self::str($data, 'version'),
            createdDate:                             self::int($data, 'createdDate'),
            lastModifiedDate:                        self::int($data, 'lastModifiedDate'),

            orderNumber:                             self::str($data, 'orderNumber'),
            status:                                  self::strOrNull($data, 'status'),
            advancePaymentStatus:                    self::strOrNull($data, 'advancePaymentStatus'),
            customerId:                              self::str($data, 'customerId'),
            orderDate:                               self::int($data, 'orderDate'),
            description:                             self::strOrNull($data, 'description'),
            responsibleUserId:                       self::strOrNull($data, 'responsibleUserId'),
            creatorId:                               self::strOrNull($data, 'creatorId'),

            salesChannel:                            self::strOrNull($data, 'salesChannel'),
            commission:                              self::strOrNull($data, 'commission'),
            commercialLanguage:                      self::strOrNull($data, 'commercialLanguage'),

            currencyConversionDate:                  self::intOrNull($data, 'currencyConversionDate'),
            currencyConversionLocked:                self::bool($data, 'currencyConversionLocked'),
            currencyConversionRate:                  self::strOrNull($data, 'currencyConversionRate'),
            recordCurrencyId:                        self::strOrNull($data, 'recordCurrencyId'),

            netAmount:                               self::strOrNull($data, 'netAmount'),
            grossAmount:                             self::strOrNull($data, 'grossAmount'),
            netAmountInCompanyCurrency:              self::strOrNull($data, 'netAmountInCompanyCurrency'),
            grossAmountInCompanyCurrency:            self::strOrNull($data, 'grossAmountInCompanyCurrency'),
            headerDiscount:                          self::strOrNull($data, 'headerDiscount'),
            headerSurcharge:                         self::strOrNull($data, 'headerSurcharge'),
            nonStandardTaxId:                        self::strOrNull($data, 'nonStandardTaxId'),

            quotationId:                             self::strOrNull($data, 'quotationId'),
            invoiceRecipientId:                      self::strOrNull($data, 'invoiceRecipientId'),
            orderNumberAtCustomer:                   self::strOrNull($data, 'orderNumberAtCustomer'),

            advancePaymentAmount:                    self::strOrNull($data, 'advancePaymentAmount'),
            applyShippingCostsOnlyOnce:              self::bool($data, 'applyShippingCostsOnlyOnce'),

            cashAccountId:                           self::strOrNull($data, 'cashAccountId'),
            paymentMethodId:                         self::strOrNull($data, 'paymentMethodId'),
            termOfPaymentId:                         self::strOrNull($data, 'termOfPaymentId'),
            salesOrderPaymentType:                   self::strOrNull($data, 'salesOrderPaymentType'),
            sepaDirectDebitMandateId:                self::strOrNull($data, 'sepaDirectDebitMandateId'),

            customerHabitualExporterLetterOfIntentId: self::strOrNull($data, 'customerHabitualExporterLetterOfIntentId'),
            defaultShippingCarrierId:                self::strOrNull($data, 'defaultShippingCarrierId'),
            defaultShippingReturnCarrierId:          self::strOrNull($data, 'defaultShippingReturnCarrierId'),
            fulfillmentProviderId:                   self::strOrNull($data, 'fulfillmentProviderId'),
            shipmentMethodId:                        self::strOrNull($data, 'shipmentMethodId'),
            warehouseId:                             self::strOrNull($data, 'warehouseId'),

            plannedDeliveryDate:                     self::intOrNull($data, 'plannedDeliveryDate'),
            plannedProjectEndDate:                   self::intOrNull($data, 'plannedProjectEndDate'),
            plannedProjectStartDate:                 self::intOrNull($data, 'plannedProjectStartDate'),
            plannedShippingDate:                     self::intOrNull($data, 'plannedShippingDate'),
            pricingDate:                             self::intOrNull($data, 'pricingDate'),
            servicePeriodFrom:                       self::intOrNull($data, 'servicePeriodFrom'),
            servicePeriodTo:                         self::intOrNull($data, 'servicePeriodTo'),

            invoiced:                                self::bool($data, 'invoiced'),
            paid:                                    self::bool($data, 'paid'),
            shipped:                                 self::bool($data, 'shipped'),
            servicesFinished:                        self::bool($data, 'servicesFinished'),

            onlyServices:                            self::bool($data, 'onlyServices'),
            disableRecordEmailingRule:               self::bool($data, 'disableRecordEmailingRule'),
            factoring:                               self::bool($data, 'factoring'),
            sentToRecipient:                         self::bool($data, 'sentToRecipient'),
            template:                                self::bool($data, 'template'),
            projectModeActive:                       self::bool($data, 'projectModeActive'),
            shippingLabelsCount:                     self::int($data, 'shippingLabelsCount'),

            projectGoals:                            self::strOrNull($data, 'projectGoals'),
            recordAsn:                               self::strOrNull($data, 'recordAsn'),
            recordComment:                           self::strOrNull($data, 'recordComment'),
            recordCommentInheritance:                self::bool($data, 'recordCommentInheritance'),
            recordFreeText:                          self::strOrNull($data, 'recordFreeText'),
            recordFreeTextInheritance:               self::bool($data, 'recordFreeTextInheritance'),
            recordOpening:                           self::strOrNull($data, 'recordOpening'),
            recordOpeningInheritance:                self::bool($data, 'recordOpeningInheritance'),
            note:                                    self::strOrNull($data, 'note'),
            dispatchCountryCode:                     self::strOrNull($data, 'dispatchCountryCode'),

            deliveryAddress:                         $deliveryAddress,
            invoiceAddress:                          $invoiceAddress,
            recordAddress:                           $recordAddress,

            orderItems:                              array_map(
                static fn(array $item) => SalesOrderItemDTO::fromArray($item),
                self::arr($data, 'orderItems'),
            ),
            commissionSalesPartners:                 array_map(
                static fn(array $item) => CommissionSalesPartnerDTO::fromArray($item),
                self::arr($data, 'commissionSalesPartners'),
            ),
            payments:                                array_map(
                static fn(array $item) => SalesOrderPaymentDTO::fromArray($item),
                self::arr($data, 'payments'),
            ),
            shippingCostItems:                       array_map(
                static fn(array $item) => ShippingCostItemDTO::fromArray($item),
                self::arr($data, 'shippingCostItems'),
            ),
            statusHistory:                           array_map(
                static fn(array $item) => StatusHistoryDTO::fromArray($item),
                self::arr($data, 'statusHistory'),
            ),
            customAttributes:                        array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
            tags:                                    self::arr($data, 'tags'),

            deliveryEmailAddresses:                  isset($data['deliveryEmailAddresses']) && is_array($data['deliveryEmailAddresses'])
                                                         ? EmailAddressesDTO::fromArray($data['deliveryEmailAddresses'])
                                                         : null,
            recordEmailAddresses:                    isset($data['recordEmailAddresses']) && is_array($data['recordEmailAddresses'])
                                                         ? EmailAddressesDTO::fromArray($data['recordEmailAddresses'])
                                                         : null,
            salesInvoiceEmailAddresses:              isset($data['salesInvoiceEmailAddresses']) && is_array($data['salesInvoiceEmailAddresses'])
                                                         ? EmailAddressesDTO::fromArray($data['salesInvoiceEmailAddresses'])
                                                         : null,
            ecommerceOrder:                          isset($data['ecommerceOrder']) && is_array($data['ecommerceOrder'])
                                                         ? EcommerceOrderDTO::fromArray($data['ecommerceOrder'])
                                                         : null,
            projectMembers:                          self::arr($data, 'projectMembers'),
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
     * Returns the planned shipping date as a DateTimeImmutable object.
     */
    public function getPlannedShippingDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['plannedShippingDate' => $this->plannedShippingDate], 'plannedShippingDate');
    }

    /**
     * Returns the service period start as a DateTimeImmutable object.
     */
    public function getServicePeriodFrom(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['servicePeriodFrom' => $this->servicePeriodFrom], 'servicePeriodFrom');
    }

    /**
     * Returns the service period end as a DateTimeImmutable object.
     */
    public function getServicePeriodTo(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['servicePeriodTo' => $this->servicePeriodTo], 'servicePeriodTo');
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
     * Returns true if the order is fully invoiced, shipped and paid.
     */
    public function isFullyFulfilled(): bool
    {
        return $this->invoiced && $this->shipped && $this->paid;
    }
}
