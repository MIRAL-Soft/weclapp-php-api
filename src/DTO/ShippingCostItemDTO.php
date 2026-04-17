<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents a shipping cost line item on a sales order, sales invoice or quotation.
 *
 * Maps to the salesOrderShippingCostItem / salesInvoiceShippingCostItem /
 * quotationShippingCostItem schemas — all three are structurally identical
 * except that salesOrder adds `ecommerceOrderItemIds`.
 *
 * The `ecommerceOrderItemIds` field is always present but will be an empty
 * array for invoice and quotation shipping cost items.
 */
final class ShippingCostItemDTO extends AbstractDTO
{
    /**
     * @param string                       $id                              Internal weclapp UUID (readOnly).
     * @param string                       $version                         Optimistic locking version string (readOnly).
     * @param int                          $createdDate                     Creation timestamp in epoch milliseconds (readOnly).
     * @param int                          $lastModifiedDate                Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null                  $articleId                       ID of the shipping article.
     * @param string|null                  $discountPercentage              Discount percentage as a decimal string.
     * @param string|null                  $grossAmount                     Gross shipping amount (readOnly).
     * @param string|null                  $grossAmountInCompanyCurrency    Gross amount in company currency (readOnly).
     * @param bool                         $manualUnitCost                  True if the unit cost was entered manually.
     * @param bool                         $manualUnitPrice                 True if the unit price was entered manually.
     * @param string|null                  $netAmount                       Net shipping amount (readOnly).
     * @param string|null                  $netAmountInCompanyCurrency      Net amount in company currency (readOnly).
     * @param string|null                  $taxId                           ID of the applied tax rate.
     * @param string|null                  $unitCost                        Purchase cost per unit as a decimal string.
     * @param string|null                  $unitCostInCompanyCurrency       Unit cost in company currency (readOnly).
     * @param string|null                  $unitPrice                       Sales price per unit as a decimal string.
     * @param string|null                  $unitPriceInCompanyCurrency      Unit price in company currency (readOnly).
     * @param array                        $ecommerceOrderItemIds           Linked e-commerce order item IDs (salesOrder only).
     * @param list<ReductionAdditionItemDTO> $reductionAdditionItems        Surcharge / discount breakdown sub-items.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $articleId,
        public readonly ?string $discountPercentage,
        public readonly ?string $grossAmount,
        public readonly ?string $grossAmountInCompanyCurrency,
        public readonly bool    $manualUnitCost,
        public readonly bool    $manualUnitPrice,
        public readonly ?string $netAmount,
        public readonly ?string $netAmountInCompanyCurrency,
        public readonly ?string $taxId,
        public readonly ?string $unitCost,
        public readonly ?string $unitCostInCompanyCurrency,
        public readonly ?string $unitPrice,
        public readonly ?string $unitPriceInCompanyCurrency,
        public readonly array   $ecommerceOrderItemIds,
        public readonly array   $reductionAdditionItems,
    ) {}

    /**
     * Create a ShippingCostItemDTO from a raw weclapp API response array.
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
            discountPercentage:           self::strOrNull($data, 'discountPercentage'),
            grossAmount:                  self::strOrNull($data, 'grossAmount'),
            grossAmountInCompanyCurrency: self::strOrNull($data, 'grossAmountInCompanyCurrency'),
            manualUnitCost:               self::bool($data, 'manualUnitCost'),
            manualUnitPrice:              self::bool($data, 'manualUnitPrice'),
            netAmount:                    self::strOrNull($data, 'netAmount'),
            netAmountInCompanyCurrency:   self::strOrNull($data, 'netAmountInCompanyCurrency'),
            taxId:                        self::strOrNull($data, 'taxId'),
            unitCost:                     self::strOrNull($data, 'unitCost'),
            unitCostInCompanyCurrency:    self::strOrNull($data, 'unitCostInCompanyCurrency'),
            unitPrice:                    self::strOrNull($data, 'unitPrice'),
            unitPriceInCompanyCurrency:   self::strOrNull($data, 'unitPriceInCompanyCurrency'),
            ecommerceOrderItemIds:        self::arr($data, 'ecommerceOrderItemIds'),
            reductionAdditionItems:       array_map(
                static fn(array $item) => ReductionAdditionItemDTO::fromArray($item),
                self::arr($data, 'reductionAdditionItems'),
            ),
        );
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
     * Returns the unit price as a float, or null if not set.
     */
    public function getUnitPrice(): ?float
    {
        return $this->unitPrice !== null ? (float) $this->unitPrice : null;
    }
}
