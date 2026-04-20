<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents an Article (product/service) from the weclapp API.
 *
 * Articles map to the /api/v2/article endpoint.
 * All 94 fields of the weclapp OpenAPI article schema are covered.
 *
 * Note on field naming vs. previous DTO versions:
 *   - longText       replaces the former descriptionLong (correct API key)
 *   - unitId         replaces the former unit (correct API key; use /unit/{id} to resolve)
 *   - availableInSale replaces the former sellable
 *
 * Fields salesPrice, purchasePrice, availableStock and reservedStock are NOT
 * part of the base article schema; they are resolved from articlePrices and
 * separate stock endpoints if needed.
 *
 * @see \miralsoft\weclapp\api\Resource\ArticleResource
 * @see \miralsoft\weclapp\api\DTO\ArticlePriceDTO
 * @see \miralsoft\weclapp\api\DTO\ArticleImageDTO
 */
final class ArticleDTO extends AbstractDTO
{
    /**
     * @param string                                    $id                                    Internal weclapp UUID (readOnly).
     * @param string                                    $version                               Optimistic locking version string (readOnly).
     * @param int                                       $createdDate                           Creation timestamp in epoch milliseconds (readOnly).
     * @param int                                       $lastModifiedDate                      Last modification timestamp in epoch milliseconds (readOnly).
     *
     * @param string                                    $articleNumber                         Unique article / SKU number (e.g. "ART-10042").
     * @param string                                    $name                                  Short article name.
     * @param string|null                               $description                           Short HTML description.
     * @param string|null                               $longText                              Long HTML description (API key: longText — formerly descriptionLong).
     * @param string|null                               $shortDescription1                     Short description line 1.
     * @param string|null                               $shortDescription2                     Short description line 2.
     * @param string|null                               $internalNote                          Internal HTML note (not visible to customers).
     * @param string|null                               $matchCode                             Alternative search code / alias.
     * @param string|null                               $articleType                           Article type (enum).
     * @param string|null                               $systemCode                            System code identifier.
     * @param string|null                               $barcode                               EAN/barcode string.
     * @param string|null                               $ean                                   European Article Number.
     * @param string|null                               $catalogCode                           Catalog code.
     *
     * @param bool                                      $active                                Whether the article is active.
     * @param bool                                      $availableInSale                       Whether the article can be sold (API key: availableInSale — formerly sellable).
     * @param bool                                      $productionArticle                     Whether the article is manufactured in-house.
     * @param bool                                      $serialNumberRequired                  Whether serial numbers are required.
     * @param bool                                      $batchNumberRequired                   Whether batch numbers are required.
     * @param bool                                      $billOfMaterialPartDeliveryPossible    Whether partial delivery is possible for BOM articles.
     * @param bool                                      $showOnDeliveryNote                    Whether the article appears on delivery notes.
     * @param bool                                      $applyCashDiscount                     Whether cash discounts apply to this article.
     * @param bool                                      $defineIndividualTaskTemplates         Whether individual task templates are defined.
     * @param bool                                      $useAvailableForSalesChannels          Whether the sales channel availability filter is active.
     * @param bool                                      $useSalesBillOfMaterialItemPrices      Whether prices from the sales BOM items are used.
     * @param bool                                      $useSalesBillOfMaterialItemPricesForPurchase Whether sales BOM item prices are also used for purchasing.
     * @param bool                                      $useSalesBillOfMaterialSubitemCosts    Whether sub-item costs from the sales BOM are used.
     *
     * @param string|null                               $unitId                                ID of the base unit of measure (API key: unitId — formerly unit).
     * @param string|null                               $articleCategoryId                     ID of the assigned article category.
     * @param string|null                               $accountId                             ID of the revenue account (accounting integration).
     * @param string|null                               $accountingCodeId                      ID of the accounting code.
     * @param string|null                               $expenseAccountId                      ID of the expense account.
     * @param string|null                               $taxRateType                           Tax rate type (enum).
     * @param string|null                               $invoicingType                         Invoicing type (enum: EFFORT / FIXED_PRICE).
     * @param string|null                               $ratingId                              ID of the article rating.
     * @param string|null                               $statusId                              ID of the article status.
     * @param string|null                               $manufacturerId                        ID of the manufacturer.
     * @param string|null                               $manufacturerPartNumber                Manufacturer's part number.
     * @param string|null                               $customsTariffNumberId                 ID of the customs tariff number.
     * @param string|null                               $customsDescription                    Customs description text.
     * @param string|null                               $countryOfOriginCode                   ISO country code of origin.
     * @param string|null                               $primarySupplySourceId                 ID of the primary supply source.
     * @param string|null                               $loadingEquipmentArticleId             ID of the loading equipment article.
     * @param string|null                               $defaultLoadingEquipmentIdentifierId   ID of the default loading equipment identifier.
     * @param string|null                               $serviceArticleForServiceQuotaBookingId ID of the service article used for quota bookings.
     * @param string|null                               $salesCostCenterId                     ID of the sales cost centre.
     * @param string|null                               $purchaseCostCenterId                  ID of the purchase cost centre.
     * @param string|null                               $packagingUnitBaseArticleId            ID of the packaging unit base article.
     * @param string|null                               $packagingUnitParentArticleId          ID of the packaging unit parent article.
     * @param string|null                               $defaultPriceCalculationType           Default price calculation type (enum).
     * @param string|null                               $marginCalculationPriceType            Margin calculation price type (enum).
     * @param string|null                               $contractBillingCycle                  Contract billing cycle (enum).
     * @param string|null                               $contractBillingMode                   Contract billing mode (enum).
     * @param string|null                               $productionConfigurationRule            Production configuration rule (enum).
     * @param string|null                               $recordItemGroupName                   Record item group name.
     * @param string|null                               $producerType                          Producer type identifier.
     *
     * @param string|null                               $commissionRate                        Commission rate as a decimal string.
     * @param string|null                               $minimumStockQuantity                  Minimum stock quantity as a decimal string.
     * @param string|null                               $minimumPurchaseQuantity               Minimum purchase quantity as a decimal string.
     * @param string|null                               $fixedPurchaseQuantity                 Fixed purchase quantity as a decimal string.
     * @param string|null                               $targetStockQuantity                   Target stock quantity as a decimal string.
     * @param string|null                               $serviceQuotaQuantity                  Service quota quantity as a decimal string.
     * @param string|null                               $articleGrossWeight                    Gross weight as a decimal string.
     * @param string|null                               $articleNetWeight                      Net weight as a decimal string.
     * @param string|null                               $articleLength                         Length as a decimal string.
     * @param string|null                               $articleWidth                          Width as a decimal string.
     * @param string|null                               $articleHeight                         Height as a decimal string.
     *
     * @param int                                       $lowLevelCode                          Low-level code for MRP (readOnly).
     * @param int                                       $packagingQuantity                     Packaging quantity.
     * @param int                                       $averageDeliveryTime                   Average delivery time in days.
     * @param int                                       $procurementLeadDays                   Procurement lead time in days.
     * @param int                                       $safetyStockDays                       Safety stock buffer in days.
     * @param int|null                                  $plannedWorkingTimePerUnit             Planned working time per unit in minutes.
     * @param int|null                                  $expirationDays                        Shelf-life / expiration days.
     * @param int|null                                  $launchDate                            Product launch date in epoch milliseconds.
     * @param int|null                                  $sellFromDate                          Earliest sellable date in epoch milliseconds.
     * @param int|null                                  $sellByDate                            Latest sellable date in epoch milliseconds.
     * @param int|null                                  $supportUntilDate                      End-of-support date in epoch milliseconds.
     *
     * @param list<ArticleImageDTO>                     $articleImages                         Article images.
     * @param list<ArticlePriceDTO>                     $articlePrices                         Sales price entries (customer/channel/scale-specific).
     * @param list<ArticleCalculationPriceDTO>          $articleCalculationPrices              Calculation/purchase price entries.
     * @param list<ArticleAlternativeQuantityDTO>       $articleAlternativeQuantities          Warehouse-specific alternative quantity configurations.
     * @param list<CustomerSpecificArticleAttributesDTO> $customerArticleNumbers               Customer-specific article number mappings.
     * @param list<QuantityConversionDTO>               $quantityConversions                   Unit-of-measure quantity conversions.
     * @param list<SupplySourceDTO>                     $supplySources                         Procurement supply sources.
     * @param list<BillOfMaterialItemDTO>               $productionBillOfMaterialItems         Production bill of materials components.
     * @param list<BillOfMaterialItemDTO>               $salesBillOfMaterialItems              Sales bill of materials components.
     * @param list<CustomAttributeDTO>                  $customAttributes                      Custom attribute values.
     * @param list<array>                               $defaultStoragePlaces                  Default storage places (raw onlyId objects).
     * @param list<array>                               $availableForSalesChannels             Sales channel availability entries (raw).
     * @param list<array>                               $tags                                  Tag objects.
     */
    public function __construct(
        // Identity
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,

        // Core identification
        public readonly string  $articleNumber,
        public readonly string  $name,
        public readonly ?string $description,
        public readonly ?string $longText,
        public readonly ?string $shortDescription1,
        public readonly ?string $shortDescription2,
        public readonly ?string $internalNote,
        public readonly ?string $matchCode,
        public readonly ?string $articleType,
        public readonly ?string $systemCode,
        public readonly ?string $barcode,
        public readonly ?string $ean,
        public readonly ?string $catalogCode,

        // Flags
        public readonly bool    $active,
        public readonly bool    $availableInSale,
        public readonly bool    $productionArticle,
        public readonly bool    $serialNumberRequired,
        public readonly bool    $batchNumberRequired,
        public readonly bool    $billOfMaterialPartDeliveryPossible,
        public readonly bool    $showOnDeliveryNote,
        public readonly bool    $applyCashDiscount,
        public readonly bool    $defineIndividualTaskTemplates,
        public readonly bool    $useAvailableForSalesChannels,
        public readonly bool    $useSalesBillOfMaterialItemPrices,
        public readonly bool    $useSalesBillOfMaterialItemPricesForPurchase,
        public readonly bool    $useSalesBillOfMaterialSubitemCosts,

        // References
        public readonly ?string $unitId,
        public readonly ?string $articleCategoryId,
        public readonly ?string $accountId,
        public readonly ?string $accountingCodeId,
        public readonly ?string $expenseAccountId,
        public readonly ?string $taxRateType,
        public readonly ?string $invoicingType,
        public readonly ?string $ratingId,
        public readonly ?string $statusId,
        public readonly ?string $manufacturerId,
        public readonly ?string $manufacturerPartNumber,
        public readonly ?string $customsTariffNumberId,
        public readonly ?string $customsDescription,
        public readonly ?string $countryOfOriginCode,
        public readonly ?string $primarySupplySourceId,
        public readonly ?string $loadingEquipmentArticleId,
        public readonly ?string $defaultLoadingEquipmentIdentifierId,
        public readonly ?string $serviceArticleForServiceQuotaBookingId,
        public readonly ?string $salesCostCenterId,
        public readonly ?string $purchaseCostCenterId,
        public readonly ?string $packagingUnitBaseArticleId,
        public readonly ?string $packagingUnitParentArticleId,
        public readonly ?string $defaultPriceCalculationType,
        public readonly ?string $marginCalculationPriceType,
        public readonly ?string $contractBillingCycle,
        public readonly ?string $contractBillingMode,
        public readonly ?string $productionConfigurationRule,
        public readonly ?string $recordItemGroupName,
        public readonly ?string $producerType,

        // Decimal quantities / measures
        public readonly ?string $commissionRate,
        public readonly ?string $minimumStockQuantity,
        public readonly ?string $minimumPurchaseQuantity,
        public readonly ?string $fixedPurchaseQuantity,
        public readonly ?string $targetStockQuantity,
        public readonly ?string $serviceQuotaQuantity,
        public readonly ?string $articleGrossWeight,
        public readonly ?string $articleNetWeight,
        public readonly ?string $articleLength,
        public readonly ?string $articleWidth,
        public readonly ?string $articleHeight,

        // Integer fields
        public readonly int     $lowLevelCode,
        public readonly int     $packagingQuantity,
        public readonly int     $averageDeliveryTime,
        public readonly int     $procurementLeadDays,
        public readonly int     $safetyStockDays,
        public readonly ?int    $plannedWorkingTimePerUnit,
        public readonly ?int    $expirationDays,
        public readonly ?int    $launchDate,
        public readonly ?int    $sellFromDate,
        public readonly ?int    $sellByDate,
        public readonly ?int    $supportUntilDate,

        // Typed nested arrays
        public readonly array   $articleImages,
        public readonly array   $articlePrices,
        public readonly array   $articleCalculationPrices,
        public readonly array   $articleAlternativeQuantities,
        public readonly array   $customerArticleNumbers,
        public readonly array   $quantityConversions,
        public readonly array   $supplySources,
        public readonly array   $productionBillOfMaterialItems,
        public readonly array   $salesBillOfMaterialItems,
        public readonly array   $customAttributes,

        // Raw arrays
        public readonly array   $defaultStoragePlaces,
        public readonly array   $availableForSalesChannels,
        public readonly array   $tags,
    ) {}

    /**
     * Create an ArticleDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:               self::str($data, 'id'),
            version:          self::str($data, 'version'),
            createdDate:      self::int($data, 'createdDate'),
            lastModifiedDate: self::int($data, 'lastModifiedDate'),

            articleNumber:    self::str($data, 'articleNumber'),
            name:             self::str($data, 'name'),
            description:      self::strOrNull($data, 'description'),
            longText:         self::strOrNull($data, 'longText'),
            shortDescription1: self::strOrNull($data, 'shortDescription1'),
            shortDescription2: self::strOrNull($data, 'shortDescription2'),
            internalNote:     self::strOrNull($data, 'internalNote'),
            matchCode:        self::strOrNull($data, 'matchCode'),
            articleType:      self::strOrNull($data, 'articleType'),
            systemCode:       self::strOrNull($data, 'systemCode'),
            barcode:          self::strOrNull($data, 'barcode'),
            ean:              self::strOrNull($data, 'ean'),
            catalogCode:      self::strOrNull($data, 'catalogCode'),

            active:                                      self::bool($data, 'active', true),
            availableInSale:                             self::bool($data, 'availableInSale', true),
            productionArticle:                           self::bool($data, 'productionArticle'),
            serialNumberRequired:                        self::bool($data, 'serialNumberRequired'),
            batchNumberRequired:                         self::bool($data, 'batchNumberRequired'),
            billOfMaterialPartDeliveryPossible:          self::bool($data, 'billOfMaterialPartDeliveryPossible'),
            showOnDeliveryNote:                          self::bool($data, 'showOnDeliveryNote', true),
            applyCashDiscount:                           self::bool($data, 'applyCashDiscount'),
            defineIndividualTaskTemplates:               self::bool($data, 'defineIndividualTaskTemplates'),
            useAvailableForSalesChannels:                self::bool($data, 'useAvailableForSalesChannels'),
            useSalesBillOfMaterialItemPrices:            self::bool($data, 'useSalesBillOfMaterialItemPrices'),
            useSalesBillOfMaterialItemPricesForPurchase: self::bool($data, 'useSalesBillOfMaterialItemPricesForPurchase'),
            useSalesBillOfMaterialSubitemCosts:          self::bool($data, 'useSalesBillOfMaterialSubitemCosts'),

            unitId:                                 self::strOrNull($data, 'unitId'),
            articleCategoryId:                      self::strOrNull($data, 'articleCategoryId'),
            accountId:                              self::strOrNull($data, 'accountId'),
            accountingCodeId:                       self::strOrNull($data, 'accountingCodeId'),
            expenseAccountId:                       self::strOrNull($data, 'expenseAccountId'),
            taxRateType:                            self::strOrNull($data, 'taxRateType'),
            invoicingType:                          self::strOrNull($data, 'invoicingType'),
            ratingId:                               self::strOrNull($data, 'ratingId'),
            statusId:                               self::strOrNull($data, 'statusId'),
            manufacturerId:                         self::strOrNull($data, 'manufacturerId'),
            manufacturerPartNumber:                 self::strOrNull($data, 'manufacturerPartNumber'),
            customsTariffNumberId:                  self::strOrNull($data, 'customsTariffNumberId'),
            customsDescription:                     self::strOrNull($data, 'customsDescription'),
            countryOfOriginCode:                    self::strOrNull($data, 'countryOfOriginCode'),
            primarySupplySourceId:                  self::strOrNull($data, 'primarySupplySourceId'),
            loadingEquipmentArticleId:              self::strOrNull($data, 'loadingEquipmentArticleId'),
            defaultLoadingEquipmentIdentifierId:    self::strOrNull($data, 'defaultLoadingEquipmentIdentifierId'),
            serviceArticleForServiceQuotaBookingId: self::strOrNull($data, 'serviceArticleForServiceQuotaBookingId'),
            salesCostCenterId:                      self::strOrNull($data, 'salesCostCenterId'),
            purchaseCostCenterId:                   self::strOrNull($data, 'purchaseCostCenterId'),
            packagingUnitBaseArticleId:             self::strOrNull($data, 'packagingUnitBaseArticleId'),
            packagingUnitParentArticleId:           self::strOrNull($data, 'packagingUnitParentArticleId'),
            defaultPriceCalculationType:            self::strOrNull($data, 'defaultPriceCalculationType'),
            marginCalculationPriceType:             self::strOrNull($data, 'marginCalculationPriceType'),
            contractBillingCycle:                   self::strOrNull($data, 'contractBillingCycle'),
            contractBillingMode:                    self::strOrNull($data, 'contractBillingMode'),
            productionConfigurationRule:            self::strOrNull($data, 'productionConfigurationRule'),
            recordItemGroupName:                    self::strOrNull($data, 'recordItemGroupName'),
            producerType:                           self::strOrNull($data, 'producerType'),

            commissionRate:           self::strOrNull($data, 'commissionRate'),
            minimumStockQuantity:     self::strOrNull($data, 'minimumStockQuantity'),
            minimumPurchaseQuantity:  self::strOrNull($data, 'minimumPurchaseQuantity'),
            fixedPurchaseQuantity:    self::strOrNull($data, 'fixedPurchaseQuantity'),
            targetStockQuantity:      self::strOrNull($data, 'targetStockQuantity'),
            serviceQuotaQuantity:     self::strOrNull($data, 'serviceQuotaQuantity'),
            articleGrossWeight:       self::strOrNull($data, 'articleGrossWeight'),
            articleNetWeight:         self::strOrNull($data, 'articleNetWeight'),
            articleLength:            self::strOrNull($data, 'articleLength'),
            articleWidth:             self::strOrNull($data, 'articleWidth'),
            articleHeight:            self::strOrNull($data, 'articleHeight'),

            lowLevelCode:             self::int($data, 'lowLevelCode'),
            packagingQuantity:        self::int($data, 'packagingQuantity'),
            averageDeliveryTime:      self::int($data, 'averageDeliveryTime'),
            procurementLeadDays:      self::int($data, 'procurementLeadDays'),
            safetyStockDays:          self::int($data, 'safetyStockDays'),
            plannedWorkingTimePerUnit: self::intOrNull($data, 'plannedWorkingTimePerUnit'),
            expirationDays:           self::intOrNull($data, 'expirationDays'),
            launchDate:               self::intOrNull($data, 'launchDate'),
            sellFromDate:             self::intOrNull($data, 'sellFromDate'),
            sellByDate:               self::intOrNull($data, 'sellByDate'),
            supportUntilDate:         self::intOrNull($data, 'supportUntilDate'),

            articleImages: array_map(
                static fn(array $i) => ArticleImageDTO::fromArray($i),
                self::arr($data, 'articleImages'),
            ),
            articlePrices: array_map(
                static fn(array $i) => ArticlePriceDTO::fromArray($i),
                self::arr($data, 'articlePrices'),
            ),
            articleCalculationPrices: array_map(
                static fn(array $i) => ArticleCalculationPriceDTO::fromArray($i),
                self::arr($data, 'articleCalculationPrices'),
            ),
            articleAlternativeQuantities: array_map(
                static fn(array $i) => ArticleAlternativeQuantityDTO::fromArray($i),
                self::arr($data, 'articleAlternativeQuantities'),
            ),
            customerArticleNumbers: array_map(
                static fn(array $i) => CustomerSpecificArticleAttributesDTO::fromArray($i),
                self::arr($data, 'customerArticleNumbers'),
            ),
            quantityConversions: array_map(
                static fn(array $i) => QuantityConversionDTO::fromArray($i),
                self::arr($data, 'quantityConversions'),
            ),
            supplySources: array_map(
                static fn(array $i) => SupplySourceDTO::fromArray($i),
                self::arr($data, 'supplySources'),
            ),
            productionBillOfMaterialItems: array_map(
                static fn(array $i) => BillOfMaterialItemDTO::fromArray($i),
                self::arr($data, 'productionBillOfMaterialItems'),
            ),
            salesBillOfMaterialItems: array_map(
                static fn(array $i) => BillOfMaterialItemDTO::fromArray($i),
                self::arr($data, 'salesBillOfMaterialItems'),
            ),
            customAttributes: array_map(
                static fn(array $i) => CustomAttributeDTO::fromArray($i),
                self::arr($data, 'customAttributes'),
            ),

            defaultStoragePlaces:      self::arr($data, 'defaultStoragePlaces'),
            availableForSalesChannels: self::arr($data, 'availableForSalesChannels'),
            tags:                      self::arr($data, 'tags'),
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
     * Returns the product launch date as a DateTimeImmutable object.
     */
    public function getLaunchDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['launchDate' => $this->launchDate], 'launchDate');
    }

    /**
     * Returns the sell-from date as a DateTimeImmutable object.
     */
    public function getSellFromDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['sellFromDate' => $this->sellFromDate], 'sellFromDate');
    }

    /**
     * Returns the sell-by (best-before) date as a DateTimeImmutable object.
     */
    public function getSellByDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['sellByDate' => $this->sellByDate], 'sellByDate');
    }

    /**
     * Returns the end-of-support date as a DateTimeImmutable object.
     */
    public function getSupportUntilDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['supportUntilDate' => $this->supportUntilDate], 'supportUntilDate');
    }

    /**
     * Returns the main image, or null if no images are assigned.
     */
    public function getMainImage(): ?ArticleImageDTO
    {
        foreach ($this->articleImages as $image) {
            if ($image->mainImage) {
                return $image;
            }
        }

        return $this->articleImages[0] ?? null;
    }

    /**
     * Returns true if the article is a BOM (bill of materials) article.
     */
    public function isBillOfMaterial(): bool
    {
        return !empty($this->productionBillOfMaterialItems)
            || !empty($this->salesBillOfMaterialItems);
    }
}
