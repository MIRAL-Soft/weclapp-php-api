<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;
use miralsoft\weclapp\api\Enum\CustomAttributeType;

/**
 * Represents a custom attribute *definition* from the weclapp API.
 *
 * A definition describes a user-defined field (the "schema"): its key, label,
 * value type and which entity types it applies to. The concrete *values* for a
 * given entity instance are carried by {@see CustomAttributeDTO}.
 *
 * Maps to the `customAttributeDefinition` schema — `/api/v2/customAttributeDefinition`.
 *
 * **Entity scoping:** weclapp scopes a definition through the `entities` array
 * (e.g. `["salesOrder"]`), not through a separate `attributeEntityType` field.
 *
 * @see \miralsoft\weclapp\api\Resource\CustomAttributeDefinitionResource
 * @see \miralsoft\weclapp\api\DTO\CustomAttributeDTO
 * @see \miralsoft\weclapp\api\Enum\CustomAttributeType
 */
final class CustomAttributeDefinitionDTO extends AbstractDTO
{
    /**
     * @param string        $id                   Internal weclapp UUID (readOnly).
     * @param string        $version              Optimistic locking version string (readOnly).
     * @param int           $createdDate          Creation timestamp in epoch milliseconds (readOnly).
     * @param int           $lastModifiedDate     Last modification timestamp in epoch milliseconds (readOnly).
     * @param string        $attributeKey         Stable key of the field (e.g. "docbeeTicketId").
     * @param string        $label                Human-readable label.
     * @param string|null   $attributeDescription Optional longer description.
     * @param string        $attributeType        Value type. See CustomAttributeType enum.
     * @param list<string>  $entities             Entity types this definition applies to (e.g. ["salesOrder"]).
     * @param bool          $active               Whether the definition is active.
     * @param bool          $mandatory            Whether a value is required on the entity.
     * @param bool          $readOnly             Whether the value is read-only in the UI.
     * @param bool          $systemCustomAttribute Whether this is a system-managed attribute (readOnly).
     * @param string|null   $groupName            Optional UI grouping name.
     * @param list<array<string,mixed>> $selectableValues Option list for LIST/MULTISELECT_LIST types.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $attributeKey,
        public readonly string  $label,
        public readonly ?string $attributeDescription,
        public readonly string  $attributeType,
        public readonly array   $entities,
        public readonly bool    $active,
        public readonly bool    $mandatory,
        public readonly bool    $readOnly,
        public readonly bool    $systemCustomAttribute,
        public readonly ?string $groupName,
        public readonly array   $selectableValues,
    ) {}

    /**
     * Create a CustomAttributeDefinitionDTO from a raw weclapp API response array.
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
            attributeKey:          self::str($data, 'attributeKey'),
            label:                 self::str($data, 'label'),
            attributeDescription:  self::strOrNull($data, 'attributeDescription'),
            attributeType:         self::str($data, 'attributeType'),
            entities:              array_values(array_map(
                static fn ($e): string => (string) $e,
                self::arr($data, 'entities'),
            )),
            active:                self::bool($data, 'active'),
            mandatory:             self::bool($data, 'mandatory'),
            readOnly:              self::bool($data, 'readOnly'),
            systemCustomAttribute: self::bool($data, 'systemCustomAttribute'),
            groupName:             self::strOrNull($data, 'groupName'),
            selectableValues:      self::arr($data, 'selectableValues'),
        );
    }

    /**
     * Returns the attribute type as a typed enum, or null for unknown values.
     */
    public function getType(): ?CustomAttributeType
    {
        return CustomAttributeType::tryFrom($this->attributeType);
    }

    /**
     * Returns true if this definition applies to the given entity type.
     */
    public function appliesTo(string $entityType): bool
    {
        return in_array($entityType, $this->entities, true);
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
}
