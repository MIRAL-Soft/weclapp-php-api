<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a single line item (quotationItem) within a Quotation.
 *
 * Maps to the `quotationItem` schema in the weclapp API.
 * Instances are embedded inside QuotationDTO::$quotationItems.
 *
 * API key name on the parent quotation: quotationItems
 *
 * @see \miralsoft\weclapp\api\DTO\QuotationDTO
 */
final class QuotationItemDTO extends AbstractDTO
{
    /**
     * @param string                         $id                                       Internal weclapp UUID (readOnly).
     * @param string                         $version                                  Optimistic locking version string (readOnly).
     * @param int                            $createdDate                              Creation timestamp in epoch milliseconds (readOnly).
     * @param int                            $lastModifiedDate                         Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null                    $articleId                                ID of the linked article. Null for FREE_TEXT items.
     * @param string|null                    $title                                    Line item title / article name.
     * @param string|null                    $description                              HTML description of the line item.
     * @param bool                           $descriptionFixed                         If true, the description is locked and not auto-updated from the article.
     * @param string|null                    $quantity                                 Quoted quantity as a decimal string (API preserves precision).
     * @param string|null                    $unitId                                   ID of the unit of measure.
     * @param string|null                    $unitPrice                                Net unit price as a decimal string.
     * @param string|null                    $unitPriceInCompanyCurrency               Net unit price in company currency (readOnly).
     * @param string|null                    $unitCost                                 Purchase/cost price per unit as a decimal string.
     * @param string|null                    $unitCostInCompanyCurrency                Purchase/cost price per unit in company currency (readOnly).
     * @param string|null                    $discountPercentage                       Discount percentage as a decimal string.
     * @param string|null                    $grossAmount                              Total gross amount for this line (readOnly).
     * @param string|null                    $grossAmountInCompanyCurrency             Gross amount in company currency (readOnly).
     * @param string|null                    $netAmount                                Total net amount for this line (readOnly).
     * @param string|null                    $netAmountInCompanyCurrency               Net amount in company currency (readOnly).
     * @param string|null                    $netAmountForStatistics                   Net amount used for statistics calculations (readOnly).
     * @param string|null                    $netAmountForStatisticsInCompanyCurrency  Net amount for statistics in company currency (readOnly).
     * @param string|null                    $recommendedRetailPrice                   Recommended retail price as a decimal string (readOnly).
     * @param int                            $positionNumber                           Display position within the quotation (1-based).
     * @param string|null                    $itemType                                 Item type: DEFAULT, FREE_TEXT, SERVICE, SERVICE_QUOTA.
     * @param string|null                    $invoicingType                            Invoicing mode for service items: EFFORT or FIXED_PRICE.
     * @param string|null                    $note                                     Internal note visible only to staff.
     * @param string|null                    $groupName                                Group header this item belongs to.
     * @param string|null                    $parentItemId                             ID of the parent item (for sub-positions).
     * @param bool                           $addPageBreakBefore                       Insert a page break before this item in the PDF.
     * @param bool                           $alternative                              True if this item is an alternative position.
     * @param bool                           $optional                                 True if this item is an optional position.
     * @param string|null                    $taxId                                    ID of the applied tax rate.
     * @param string|null                    $itScopeId                                ID of the linked IT scope (readOnly).
     * @param bool                           $manualQuantity                           If true, the quantity was entered manually.
     * @param bool                           $manualUnitPrice                          If true, the unit price was entered manually.
     * @param bool                           $manualUnitCost                           If true, the unit cost was entered manually.
     * @param bool                           $manualPlannedWorkingTimePerUnit          If true, the planned working time was entered manually.
     * @param int|null                       $servicePeriodFrom                        Service period start date in epoch milliseconds.
     * @param int|null                       $servicePeriodTo                          Service period end date in epoch milliseconds.
     * @param int|null                       $plannedDeliveryDate                      Planned delivery date in epoch milliseconds.
     * @param int|null                       $plannedShippingDate                      Planned shipping date in epoch milliseconds.
     * @param int|null                       $plannedWorkingTimePerUnit                Planned working time per unit in minutes.
     * @param list<CommissionSalesPartnerDTO> $commissionSalesPartners                 Commission assignments for sales partners.
     * @param list<ReductionAdditionItemDTO>  $reductionAdditionItems                 Surcharge / discount sub-items.
     * @param list<array<string, mixed>>     $quotationItemRelationships               Source relationships (complex, kept raw).
     * @param list<array<string, mixed>>     $scaleValues                              Scale price values (complex, kept raw).
     * @param list<CustomAttributeDTO>       $customAttributes                        Custom attribute values.
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
        public readonly ?string $unitCost,
        public readonly ?string $unitCostInCompanyCurrency,
        public readonly ?string $discountPercentage,

        // Computed amounts (readOnly)
        public readonly ?string $grossAmount,
        public readonly ?string $grossAmountInCompanyCurrency,
        public readonly ?string $netAmount,
        public readonly ?string $netAmountInCompanyCurrency,
        public readonly ?string $netAmountForStatistics,
        public readonly ?string $netAmountForStatisticsInCompanyCurrency,
        public readonly ?string $recommendedRetailPrice,

        // Item classification
        public readonly int     $positionNumber,
        public readonly ?string $itemType,
        public readonly ?string $invoicingType,
        public readonly ?string $note,
        public readonly ?string $groupName,
        public readonly ?string $parentItemId,
        public readonly bool    $addPageBreakBefore,
        public readonly bool    $alternative,
        public readonly bool    $optional,
        public readonly ?string $taxId,
        public readonly ?string $itScopeId,

        // Manual override flags
        public readonly bool    $manualQuantity,
        public readonly bool    $manualUnitPrice,
        public readonly bool    $manualUnitCost,
        public readonly bool    $manualPlannedWorkingTimePerUnit,

        // Dates
        public readonly ?int    $servicePeriodFrom,
        public readonly ?int    $servicePeriodTo,
        public readonly ?int    $plannedDeliveryDate,
        public readonly ?int    $plannedShippingDate,
        public readonly ?int    $plannedWorkingTimePerUnit,

        // Nested typed arrays
        public readonly array   $commissionSalesPartners,
        public readonly array   $reductionAdditionItems,
        public readonly array   $quotationItemRelationships,
        public readonly array   $scaleValues,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a QuotationItemDTO from a raw weclapp API response array.
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
            unitCost:                                self::strOrNull($data, 'unitCost'),
            unitCostInCompanyCurrency:               self::strOrNull($data, 'unitCostInCompanyCurrency'),
            discountPercentage:                      self::strOrNull($data, 'discountPercentage'),

            grossAmount:                             self::strOrNull($data, 'grossAmount'),
            grossAmountInCompanyCurrency:            self::strOrNull($data, 'grossAmountInCompanyCurrency'),
            netAmount:                               self::strOrNull($data, 'netAmount'),
            netAmountInCompanyCurrency:              self::strOrNull($data, 'netAmountInCompanyCurrency'),
            netAmountForStatistics:                  self::strOrNull($data, 'netAmountForStatistics'),
            netAmountForStatisticsInCompanyCurrency: self::strOrNull($data, 'netAmountForStatisticsInCompanyCurrency'),
            recommendedRetailPrice:                  self::strOrNull($data, 'recommendedRetailPrice'),

            positionNumber:                          self::int($data, 'positionNumber'),
            itemType:                                self::strOrNull($data, 'itemType'),
            invoicingType:                           self::strOrNull($data, 'invoicingType'),
            note:                                    self::strOrNull($data, 'note'),
            groupName:                               self::strOrNull($data, 'groupName'),
            parentItemId:                            self::strOrNull($data, 'parentItemId'),
            addPageBreakBefore:                      self::bool($data, 'addPageBreakBefore'),
            alternative:                             self::bool($data, 'alternative'),
            optional:                                self::bool($data, 'optional'),
            taxId:                                   self::strOrNull($data, 'taxId'),
            itScopeId:                               self::strOrNull($data, 'itScopeId'),

            manualQuantity:                          self::bool($data, 'manualQuantity'),
            manualUnitPrice:                         self::bool($data, 'manualUnitPrice'),
            manualUnitCost:                          self::bool($data, 'manualUnitCost'),
            manualPlannedWorkingTimePerUnit:         self::bool($data, 'manualPlannedWorkingTimePerUnit'),

            servicePeriodFrom:                       self::intOrNull($data, 'servicePeriodFrom'),
            servicePeriodTo:                         self::intOrNull($data, 'servicePeriodTo'),
            plannedDeliveryDate:                     self::intOrNull($data, 'plannedDeliveryDate'),
            plannedShippingDate:                     self::intOrNull($data, 'plannedShippingDate'),
            plannedWorkingTimePerUnit:               self::intOrNull($data, 'plannedWorkingTimePerUnit'),

            commissionSalesPartners:                 array_map(
                static fn(array $item) => CommissionSalesPartnerDTO::fromArray($item),
                self::arr($data, 'commissionSalesPartners'),
            ),
            reductionAdditionItems:                  array_map(
                static fn(array $item) => ReductionAdditionItemDTO::fromArray($item),
                self::arr($data, 'reductionAdditionItems'),
            ),
            quotationItemRelationships:              self::arr($data, 'quotationItemRelationships'),
            scaleValues:                             self::arr($data, 'scaleValues'),
            customAttributes:                        array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
        );
    }

    /**
     * Returns the quoted quantity as a float, or null if not set.
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
     * Returns the gross amount for this line item as a float, or null if not set.
     */
    public function getGrossAmount(): ?float
    {
        return $this->grossAmount !== null ? (float) $this->grossAmount : null;
    }

    /**
     * Returns true if this item has no article reference (free-text position).
     */
    public function isFreeText(): bool
    {
        return $this->itemType === 'FREE_TEXT' || $this->articleId === null;
    }

    /**
     * Returns true if this item is a service position.
     */
    public function isService(): bool
    {
        return $this->itemType === 'SERVICE' || $this->itemType === 'SERVICE_QUOTA';
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
}
