<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a custom attribute value attached to a weclapp entity.
 *
 * Maps to the `customAttribute` schema. Custom attributes are dynamic fields
 * defined by the user in weclapp settings. Each instance carries the value for
 * one attribute definition on one entity.
 *
 * Only one of the value fields (booleanValue, dateValue, numberValue,
 * selectedValueId, selectedValues, stringValue, entityReferences) will be
 * populated depending on the attribute definition type.
 */
final class CustomAttributeDTO extends AbstractDTO
{
    /**
     * @param string|null  $attributeDefinitionId  ID of the custom attribute definition.
     * @param bool         $booleanValue           Value for BOOLEAN type attributes.
     * @param int|null     $dateValue              Value for DATE type attributes (epoch milliseconds).
     * @param string|null  $entityId               ID of the entity this attribute belongs to.
     * @param string|null  $numberValue            Value for NUMBER/DECIMAL type attributes (decimal string).
     * @param string|null  $selectedValueId        Selected option ID for SELECT type attributes.
     * @param string|null  $stringValue            Value for TEXT/TEXTAREA type attributes.
     * @param array        $entityReferences       List of referenced entity objects [{entityId, entityName}].
     * @param array        $selectedValues         List of selected option objects [{id}] for MULTISELECT attributes.
     */
    public function __construct(
        public readonly ?string $attributeDefinitionId,
        public readonly bool    $booleanValue,
        public readonly ?int    $dateValue,
        public readonly ?string $entityId,
        public readonly ?string $numberValue,
        public readonly ?string $selectedValueId,
        public readonly ?string $stringValue,
        public readonly array   $entityReferences,
        public readonly array   $selectedValues,
    ) {}

    /**
     * Create a CustomAttributeDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            attributeDefinitionId: self::strOrNull($data, 'attributeDefinitionId'),
            booleanValue:          self::bool($data, 'booleanValue'),
            dateValue:             self::intOrNull($data, 'dateValue'),
            entityId:              self::strOrNull($data, 'entityId'),
            numberValue:           self::strOrNull($data, 'numberValue'),
            selectedValueId:       self::strOrNull($data, 'selectedValueId'),
            stringValue:           self::strOrNull($data, 'stringValue'),
            entityReferences:      self::arr($data, 'entityReferences'),
            selectedValues:        self::arr($data, 'selectedValues'),
        );
    }

    /**
     * Returns the date value as a DateTimeImmutable object, or null if not set.
     */
    public function getDateValue(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['dateValue' => $this->dateValue], 'dateValue');
    }
}
