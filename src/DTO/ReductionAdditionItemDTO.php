<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents a single surcharge or discount sub-item on a line item or shipping cost item.
 *
 * Maps to the `reductionAdditionItem` schema. These are readOnly computed values
 * returned by the API to describe how a price was built up from scale prices,
 * special reductions, header discounts, etc.
 */
final class ReductionAdditionItemDTO extends AbstractDTO
{
    /**
     * @param int         $position               Display position within the parent item (readOnly).
     * @param string|null $source                 Source of this reduction/addition (readOnly).
     * @param bool        $specialPriceReduction  True if this is a special-price reduction (readOnly).
     * @param string|null $title                  Human-readable label for this reduction/addition (readOnly).
     * @param string|null $type                   Type identifier (readOnly).
     * @param string|null $value                  Monetary value or percentage as a decimal string (readOnly).
     */
    public function __construct(
        public readonly int     $position,
        public readonly ?string $source,
        public readonly bool    $specialPriceReduction,
        public readonly ?string $title,
        public readonly ?string $type,
        public readonly ?string $value,
    ) {}

    /**
     * Create a ReductionAdditionItemDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            position:              self::int($data, 'position'),
            source:                self::strOrNull($data, 'source'),
            specialPriceReduction: self::bool($data, 'specialPriceReduction'),
            title:                 self::strOrNull($data, 'title'),
            type:                  self::strOrNull($data, 'type'),
            value:                 self::strOrNull($data, 'value'),
        );
    }

    /**
     * Returns the value as a float, or null if not set.
     */
    public function getValue(): ?float
    {
        return $this->value !== null ? (float) $this->value : null;
    }
}
