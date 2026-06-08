<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a single line item (recurringInvoiceItem) within a Recurring Invoice.
 *
 * Maps to the recurringInvoiceItem structure embedded in
 * RecurringInvoiceDTO::$recurringInvoiceItems. Carries the article reference,
 * quantity, pricing and tax of one recurring billing position.
 *
 * @see \miralsoft\weclapp\api\DTO\RecurringInvoiceDTO
 * @see \miralsoft\weclapp\api\Enum\ItemType
 */
final class RecurringInvoiceItemDTO extends AbstractDTO
{
    /**
     * @param string       $id                           Internal weclapp UUID (readOnly).
     * @param string       $version                      Optimistic locking version string (readOnly).
     * @param int          $createdDate                  Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate             Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $articleId                    ID of the linked article. Null for FREE_TEXT items.
     * @param string|null  $title                        Line item title / article name.
     * @param string|null  $description                  HTML description of the line item.
     * @param bool         $descriptionFixed             If true, the description is locked and not auto-updated from the article.
     * @param string|null  $quantity                     Billed quantity as a decimal string.
     * @param string|null  $unitId                       ID of the unit of measure.
     * @param string|null  $unitPrice                    Net unit price as a decimal string.
     * @param string|null  $unitPriceInCompanyCurrency   Net unit price in company currency (readOnly).
     * @param string|null  $unitCost                     Cost price per unit as a decimal string.
     * @param string|null  $unitCostInCompanyCurrency    Cost price per unit in company currency (readOnly).
     * @param string|null  $discountPercentage           Discount percentage as a decimal string.
     * @param string|null  $grossAmount                  Total gross amount for this line (readOnly).
     * @param string|null  $grossAmountInCompanyCurrency Gross amount in company currency (readOnly).
     * @param string|null  $netAmount                    Total net amount for this line (readOnly).
     * @param string|null  $netAmountInCompanyCurrency   Net amount in company currency (readOnly).
     * @param int          $positionNumber               Display position within the invoice (1-based).
     * @param string|null  $itemType                     Item type. See ItemType enum (DEFAULT, FREE_TEXT, SERVICE, …).
     * @param string|null  $taxId                        ID of the applied tax rate.
     * @param bool         $addPageBreakBefore           Insert a page break before this item in the PDF.
     * @param bool         $manualQuantity               If true, the quantity was entered manually.
     * @param bool         $manualUnitPrice              If true, the unit price was entered manually.
     * @param bool         $manualUnitCost               If true, the unit cost was entered manually.
     * @param list<CommissionSalesPartnerDTO> $commissionSalesPartners Commission assignments for sales partners.
     * @param list<ReductionAdditionItemDTO>  $reductionAdditionItems  Surcharge / discount sub-items.
     * @param list<CustomAttributeDTO>        $customAttributes        Custom attribute values.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,

        public readonly ?string $articleId,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly bool    $descriptionFixed,

        public readonly ?string $quantity,
        public readonly ?string $unitId,
        public readonly ?string $unitPrice,
        public readonly ?string $unitPriceInCompanyCurrency,
        public readonly ?string $unitCost,
        public readonly ?string $unitCostInCompanyCurrency,
        public readonly ?string $discountPercentage,

        public readonly ?string $grossAmount,
        public readonly ?string $grossAmountInCompanyCurrency,
        public readonly ?string $netAmount,
        public readonly ?string $netAmountInCompanyCurrency,

        public readonly int     $positionNumber,
        public readonly ?string $itemType,
        public readonly ?string $taxId,
        public readonly bool    $addPageBreakBefore,

        public readonly bool    $manualQuantity,
        public readonly bool    $manualUnitPrice,
        public readonly bool    $manualUnitCost,

        public readonly array   $commissionSalesPartners,
        public readonly array   $reductionAdditionItems,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a RecurringInvoiceItemDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                           self::str($data, 'id'),
            version:                      self::str($data, 'version'),
            createdDate:                  self::int($data, 'createdDate'),
            lastModifiedDate:             self::int($data, 'lastModifiedDate'),

            articleId:                    self::strOrNull($data, 'articleId'),
            title:                        self::strOrNull($data, 'title'),
            description:                  self::strOrNull($data, 'description'),
            descriptionFixed:             self::bool($data, 'descriptionFixed'),

            quantity:                     self::strOrNull($data, 'quantity'),
            unitId:                       self::strOrNull($data, 'unitId'),
            unitPrice:                    self::strOrNull($data, 'unitPrice'),
            unitPriceInCompanyCurrency:   self::strOrNull($data, 'unitPriceInCompanyCurrency'),
            unitCost:                     self::strOrNull($data, 'unitCost'),
            unitCostInCompanyCurrency:    self::strOrNull($data, 'unitCostInCompanyCurrency'),
            discountPercentage:           self::strOrNull($data, 'discountPercentage'),

            grossAmount:                  self::strOrNull($data, 'grossAmount'),
            grossAmountInCompanyCurrency: self::strOrNull($data, 'grossAmountInCompanyCurrency'),
            netAmount:                    self::strOrNull($data, 'netAmount'),
            netAmountInCompanyCurrency:   self::strOrNull($data, 'netAmountInCompanyCurrency'),

            positionNumber:               self::int($data, 'positionNumber'),
            itemType:                     self::strOrNull($data, 'itemType'),
            taxId:                        self::strOrNull($data, 'taxId'),
            addPageBreakBefore:           self::bool($data, 'addPageBreakBefore'),

            manualQuantity:               self::bool($data, 'manualQuantity'),
            manualUnitPrice:              self::bool($data, 'manualUnitPrice'),
            manualUnitCost:               self::bool($data, 'manualUnitCost'),

            commissionSalesPartners:      array_map(
                static fn (array $item) => CommissionSalesPartnerDTO::fromArray($item),
                self::arr($data, 'commissionSalesPartners'),
            ),
            reductionAdditionItems:       array_map(
                static fn (array $item) => ReductionAdditionItemDTO::fromArray($item),
                self::arr($data, 'reductionAdditionItems'),
            ),
            customAttributes:             array_map(
                static fn (array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
        );
    }

    /** Returns the billed quantity as a float, or null if not set. */
    public function getQuantity(): ?float
    {
        return $this->quantity !== null ? (float) $this->quantity : null;
    }

    /** Returns the net unit price as a float, or null if not set. */
    public function getUnitPrice(): ?float
    {
        return $this->unitPrice !== null ? (float) $this->unitPrice : null;
    }

    /** Returns the line net amount as a float, or null if not set. */
    public function getNetAmount(): ?float
    {
        return $this->netAmount !== null ? (float) $this->netAmount : null;
    }

    /** Returns the line gross amount as a float, or null if not set. */
    public function getGrossAmount(): ?float
    {
        return $this->grossAmount !== null ? (float) $this->grossAmount : null;
    }

    /** Returns the creation date as a DateTimeImmutable object. */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['createdDate' => $this->createdDate], 'createdDate');
    }

    /** Returns the last modification date as a DateTimeImmutable object. */
    public function getLastModifiedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['lastModifiedDate' => $this->lastModifiedDate], 'lastModifiedDate');
    }
}
