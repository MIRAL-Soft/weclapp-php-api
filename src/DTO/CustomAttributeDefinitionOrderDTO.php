<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents the display-order entry for a custom attribute definition.
 *
 * Maps to the `customAttributeDefinitionOrder` schema, used by the
 * `/customAttributeDefinition/readOrder` and `/updateOrder` endpoints to control
 * the order (and optional group override) in which custom fields appear in the
 * weclapp UI for a given entity type.
 *
 * @see \miralsoft\weclapp\api\Resource\CustomAttributeDefinitionResource::readOrder()
 * @see \miralsoft\weclapp\api\Resource\CustomAttributeDefinitionResource::updateOrder()
 */
final class CustomAttributeDefinitionOrderDTO extends AbstractDTO
{
    /**
     * @param string      $id                ID of the custom attribute definition.
     * @param string|null $overrideGroupName Optional UI group name override for this position.
     */
    public function __construct(
        public readonly string  $id,
        public readonly ?string $overrideGroupName,
    ) {}

    /**
     * Create a CustomAttributeDefinitionOrderDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                self::str($data, 'id'),
            overrideGroupName: self::strOrNull($data, 'overrideGroupName'),
        );
    }
}
