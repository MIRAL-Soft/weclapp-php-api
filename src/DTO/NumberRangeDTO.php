<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;
use miralsoft\weclapp\api\Enum\NumberRangeType;

/**
 * Represents a Number Range configuration from the weclapp API.
 *
 * Each number range defines the counter and prefix for one document type
 * (e.g. invoices, orders, customers). The range itself only carries the
 * entity type; the actual prefix, suffix and current counter are stored
 * in associated NumberRangeValue records.
 *
 * Maps to the numberRange schema — /api/v2/numberRange (read-only).
 *
 * @see \miralsoft\weclapp\api\DTO\NumberRangeValueDTO
 * @see \miralsoft\weclapp\api\Resource\NumberRangeResource
 * @see \miralsoft\weclapp\api\Enum\NumberRangeType
 */
final class NumberRangeDTO extends AbstractDTO
{
    /**
     * @param string $id               Internal weclapp UUID (readOnly).
     * @param string $version          Optimistic locking version string (readOnly).
     * @param int    $createdDate      Creation timestamp in epoch milliseconds (readOnly).
     * @param int    $lastModifiedDate Last modification timestamp in epoch milliseconds (readOnly).
     * @param string $type             Entity type this range applies to. See NumberRangeType enum.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $version,
        public readonly int    $createdDate,
        public readonly int    $lastModifiedDate,
        public readonly string $type,
    ) {}

    /**
     * Create a NumberRangeDTO from a raw weclapp API response array.
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
            type:             self::str($data, 'type'),
        );
    }

    /**
     * Returns the number range type as a typed enum, or null for unknown values.
     */
    public function getType(): ?NumberRangeType
    {
        return NumberRangeType::tryFrom($this->type);
    }

    /**
     * Returns the creation date as a DateTimeImmutable object.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['createdDate' => $this->createdDate], 'createdDate');
    }
}
