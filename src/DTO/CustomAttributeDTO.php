<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;
use DateTimeInterface;

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
     * @param list<array<string, mixed>> $entityReferences  List of referenced entity objects [{entityId, entityName}].
     * @param list<array<string, mixed>> $selectedValues    List of selected option objects [{id}] for MULTISELECT attributes.
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

    /**
     * Returns the populated value as a best-effort scalar, regardless of type.
     *
     * Inspects the value fields in priority order and returns the first one that
     * is set: stringValue → numberValue → dateValue → selectedValueId, falling
     * back to booleanValue.
     *
     * **Caveat:** a CustomAttributeDTO does not carry its own definition type, so
     * for ambiguous cases prefer the typed property directly (e.g. `->stringValue`).
     * For LIST/MULTISELECT/ENTITY attributes use `selectedValueId`/`selectedValues`/
     * `entityReferences` explicitly.
     */
    public function value(): string|int|bool
    {
        return match (true) {
            $this->stringValue     !== null => $this->stringValue,
            $this->numberValue     !== null => $this->numberValue,
            $this->dateValue       !== null => $this->dateValue,
            $this->selectedValueId !== null => $this->selectedValueId,
            default                         => $this->booleanValue,
        };
    }

    // -------------------------------------------------------------------------
    // Payload builders — produce the raw fragment for a customAttributes entry
    // -------------------------------------------------------------------------

    /**
     * Build a STRING/URL/LARGE_TEXT custom attribute payload fragment.
     *
     * @return array<string, mixed>
     *
     * @example
     * $client->salesOrders()->setCustomAttribute(
     *     $orderId,
     *     CustomAttributeDTO::string($defId, 'TICKET-4711')
     * );
     */
    public static function string(string $definitionId, ?string $value): array
    {
        return ['attributeDefinitionId' => $definitionId, 'stringValue' => $value];
    }

    /**
     * Build an INTEGER/DECIMAL custom attribute payload fragment.
     *
     * weclapp stores numbers as decimal strings; ints/floats are cast to string.
     *
     * @param int|float|string|null $value
     * @return array<string, mixed>
     */
    public static function number(string $definitionId, int|float|string|null $value): array
    {
        return [
            'attributeDefinitionId' => $definitionId,
            'numberValue'           => $value === null ? null : (string) $value,
        ];
    }

    /**
     * Build a BOOLEAN custom attribute payload fragment.
     *
     * @return array<string, mixed>
     */
    public static function boolean(string $definitionId, bool $value): array
    {
        return ['attributeDefinitionId' => $definitionId, 'booleanValue' => $value];
    }

    /**
     * Build a DATE custom attribute payload fragment.
     *
     * @param DateTimeInterface|int|null $value DateTime or epoch milliseconds.
     * @return array<string, mixed>
     */
    public static function date(string $definitionId, DateTimeInterface|int|null $value): array
    {
        $epochMs = $value instanceof DateTimeInterface
            ? (int) $value->format('U') * 1000 + (int) $value->format('v')
            : $value;

        return ['attributeDefinitionId' => $definitionId, 'dateValue' => $epochMs];
    }

    /**
     * Build a LIST (single-select) custom attribute payload fragment.
     *
     * @return array<string, mixed>
     */
    public static function selection(string $definitionId, ?string $selectedValueId): array
    {
        return ['attributeDefinitionId' => $definitionId, 'selectedValueId' => $selectedValueId];
    }
}
