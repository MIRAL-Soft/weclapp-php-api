<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents a single line item within a Shipment in the weclapp API.
 *
 * Maps to the shipmentItem schema. Instances are embedded inside
 * ShipmentDTO::$shipmentItems. Each item links a shipped quantity
 * to a source sales order item or purchase order item.
 *
 * @see \miralsoft\weclapp\api\DTO\ShipmentDTO
 */
final class ShipmentItemDTO extends AbstractDTO
{
    /**
     * @param string      $id                    Internal weclapp UUID.
     * @param string      $version               Record version (optimistic locking).
     * @param int         $createdDate           Creation timestamp in epoch milliseconds.
     * @param int         $lastModifiedDate      Last modification timestamp in epoch milliseconds.
     * @param bool        $addPageBreakBefore    Insert a page break before this item in the picking list PDF.
     * @param string|null $articleId             ID of the linked article.
     * @param string|null $description           HTML description of the line item.
     * @param bool        $descriptionFixed      If true, the description is locked and not auto-updated from the article.
     * @param string|null $groupName             Group header this item belongs to.
     * @param string|null $itemType              Item type (enum: itemType).
     * @param bool        $manualQuantity        If true, the quantity was entered manually.
     * @param string|null $note                  Internal note visible only to staff.
     * @param string|null $parentItemId          ID of the parent item (for sub-positions).
     * @param int         $positionNumber        Display position within the shipment (1-based).
     * @param string|null $purchaseOrderItemId   ID of the originating purchase order item.
     * @param string|null $quantity              Shipped quantity as a decimal string.
     * @param string|null $returnAssessmentId    ID of the primary return assessment record.
     * @param string|null $returnDescription     Description of the return.
     * @param string|null $returnErrorId         ID of the primary return error record.
     * @param string|null $returnReasonId        ID of the primary return reason record.
     * @param string|null $returnRectificationId ID of the primary return rectification record.
     * @param string|null $salesOrderItemId      ID of the originating sales order item.
     * @param string|null $title                 Line item title / article name.
     * @param string|null $unitId                ID of the unit of measure.
     * @param list<ItemPickDTO>        $picks               Linked warehouse picks.
     * @param list<array>              $returnAssessments   Return assessment references (raw {id} objects).
     * @param list<array>              $returnErrors        Return error references (raw {id} objects).
     * @param list<array>              $returnReasons       Return reason references (raw {id} objects).
     * @param list<array>              $returnRectifications Return rectification references (raw {id} objects).
     * @param list<CustomAttributeDTO> $customAttributes   Custom attribute values.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly bool    $addPageBreakBefore,
        public readonly ?string $articleId,
        public readonly ?string $description,
        public readonly bool    $descriptionFixed,
        public readonly ?string $groupName,
        public readonly ?string $itemType,
        public readonly bool    $manualQuantity,
        public readonly ?string $note,
        public readonly ?string $parentItemId,
        public readonly int     $positionNumber,
        public readonly ?string $purchaseOrderItemId,
        public readonly ?string $quantity,
        public readonly ?string $returnAssessmentId,
        public readonly ?string $returnDescription,
        public readonly ?string $returnErrorId,
        public readonly ?string $returnReasonId,
        public readonly ?string $returnRectificationId,
        public readonly ?string $salesOrderItemId,
        public readonly ?string $title,
        public readonly ?string $unitId,
        public readonly array   $picks,
        public readonly array   $returnAssessments,
        public readonly array   $returnErrors,
        public readonly array   $returnReasons,
        public readonly array   $returnRectifications,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a ShipmentItemDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                    self::str($data, 'id'),
            version:               self::str($data, 'version'),
            createdDate:           self::int($data, 'createdDate'),
            lastModifiedDate:      self::int($data, 'lastModifiedDate'),
            addPageBreakBefore:    self::bool($data, 'addPageBreakBefore'),
            articleId:             self::strOrNull($data, 'articleId'),
            description:           self::strOrNull($data, 'description'),
            descriptionFixed:      self::bool($data, 'descriptionFixed'),
            groupName:             self::strOrNull($data, 'groupName'),
            itemType:              self::strOrNull($data, 'itemType'),
            manualQuantity:        self::bool($data, 'manualQuantity'),
            note:                  self::strOrNull($data, 'note'),
            parentItemId:          self::strOrNull($data, 'parentItemId'),
            positionNumber:        self::int($data, 'positionNumber'),
            purchaseOrderItemId:   self::strOrNull($data, 'purchaseOrderItemId'),
            quantity:              self::strOrNull($data, 'quantity'),
            returnAssessmentId:    self::strOrNull($data, 'returnAssessmentId'),
            returnDescription:     self::strOrNull($data, 'returnDescription'),
            returnErrorId:         self::strOrNull($data, 'returnErrorId'),
            returnReasonId:        self::strOrNull($data, 'returnReasonId'),
            returnRectificationId: self::strOrNull($data, 'returnRectificationId'),
            salesOrderItemId:      self::strOrNull($data, 'salesOrderItemId'),
            title:                 self::strOrNull($data, 'title'),
            unitId:                self::strOrNull($data, 'unitId'),
            picks:                 array_map(
                static fn(array $item) => ItemPickDTO::fromArray($item),
                self::arr($data, 'picks'),
            ),
            returnAssessments:     self::arr($data, 'returnAssessments'),
            returnErrors:          self::arr($data, 'returnErrors'),
            returnReasons:         self::arr($data, 'returnReasons'),
            returnRectifications:  self::arr($data, 'returnRectifications'),
            customAttributes:      array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
        );
    }

    /**
     * Returns the shipped quantity as a float, or null if not set.
     */
    public function getQuantity(): ?float
    {
        return $this->quantity !== null ? (float) $this->quantity : null;
    }
}
