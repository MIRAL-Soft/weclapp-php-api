<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Quantity Unit (Mengeneinheit) from the weclapp API.
 *
 * Maps to the /api/v2/unit endpoint (weclapp calls them "unit" in the API,
 * "Quantity Unit" in documentation). All 7 fields of the weclapp OpenAPI
 * unit schema are covered.
 *
 * Units are either plain quantity units (Stück, Pauschal, Lizenz, …) or
 * time-based units (Stunde, Minute, …). Time-based units carry a
 * timeUnitAmount value (in seconds). Use isTimeUnit() to distinguish them.
 *
 * Verified against miralsoft tenant (2025-05-27):
 *   - Stunde (h) → timeUnitAmount = 3600  (= 3600 s = 1 h)
 *   - Stk., Kg, Lizenz, … → timeUnitAmount absent (null)
 *   - Jahr, Monat → timeUnitAmount absent despite being time-related
 *
 * @see \miralsoft\weclapp\api\Resource\QuantityUnitResource
 */
final class QuantityUnitDTO extends AbstractDTO
{
    /**
     * @param string   $id               Internal weclapp UUID (readOnly).
     * @param string   $version          Optimistic locking version string (readOnly).
     * @param int      $createdDate      Creation timestamp in epoch milliseconds (readOnly).
     * @param int      $lastModifiedDate Last modification timestamp in epoch milliseconds (readOnly).
     * @param string   $name             Display name of the unit (e.g. "h", "Stk.", "Lizenz").
     * @param ?string  $description      Long name / description (e.g. "Stunde", "Stück"). Max 60 chars.
     * @param ?int     $timeUnitAmount   Duration this unit represents, in SECONDS (not ms).
     *                                   Present only for time-based units.
     *                                   Verified: Stunde = 3600.
     *                                   Multiply by 1000 to obtain milliseconds (see getMilliseconds()).
     *                                   Note: Jahr/Monat do NOT carry this field despite being time-related.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $name,
        public readonly ?string $description,
        public readonly ?int    $timeUnitAmount,
    ) {}

    /**
     * Create a QuantityUnitDTO from a raw weclapp API response array.
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
            name:             self::str($data, 'name'),
            description:      self::strOrNull($data, 'description'),
            timeUnitAmount:   self::intOrNull($data, 'timeUnitAmount'),
        );
    }

    /**
     * Returns true if this is a time-based unit (has a timeUnitAmount).
     *
     * Use this to filter the listAll() result to only show units that make
     * sense for a time-tracking sync workflow (e.g. filling a setup dropdown).
     * Units without timeUnitAmount (Stück, Pauschal, Lizenz, Jahr, Monat, …)
     * return false and should be left untouched by automated time-sync.
     *
     * @example
     * $timeUnits = array_filter(
     *     $client->quantityUnits()->listAll(),
     *     fn(QuantityUnitDTO $u) => $u->isTimeUnit()
     * );
     */
    public function isTimeUnit(): bool
    {
        return $this->timeUnitAmount !== null;
    }

    /**
     * Returns the unit duration in milliseconds, or null for non-time units.
     *
     * Convenience wrapper around timeUnitAmount (which is stored in seconds).
     * Use this value directly as msPerUnit in time-tracking sync configuration.
     *
     * @example
     * // Stunde → 3_600_000 ms
     * $ms = $unit->getMilliseconds(); // 3600 * 1000 = 3_600_000
     */
    public function getMilliseconds(): ?int
    {
        return $this->timeUnitAmount !== null ? $this->timeUnitAmount * 1000 : null;
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
