<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Number Range Value (counter configuration) from the weclapp API.
 *
 * A NumberRangeValue holds the concrete configuration for one number series:
 * its prefix (e.g. "PR-"), suffix, current counter, and optional validity
 * period. Multiple values can exist for one NumberRange to support different
 * prefixes per sales channel or time period.
 *
 * Maps to the numberRangeValue schema — /api/v2/numberRangeValue (read-only).
 *
 * Typical DATEV use case:
 *   Fetch the proforma number range value to obtain the tenant-specific
 *   prefix, then use it to exclude proforma invoices from DATEV exports.
 *
 *   $prefix = $client->numberRanges()->getProformaInvoicePrefix(); // e.g. "PR-"
 *   $forDatev = array_filter($invoices, fn($i) => !str_starts_with($i->invoiceNumber, $prefix));
 *
 * @see \miralsoft\weclapp\api\DTO\NumberRangeDTO
 * @see \miralsoft\weclapp\api\Resource\NumberRangeValueResource
 */
final class NumberRangeValueDTO extends AbstractDTO
{
    /**
     * @param string      $id                   Internal weclapp UUID (readOnly).
     * @param string      $version              Optimistic locking version string (readOnly).
     * @param int         $createdDate          Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate     Last modification timestamp in epoch milliseconds (readOnly).
     * @param string      $numberRangeId        ID of the parent NumberRange entity (required).
     * @param int         $interval             Increment step between consecutive numbers (required).
     * @param int         $lastValue            Last issued number in this series (required, readOnly).
     * @param int|null    $length               Zero-padded length of the numeric part, or null for no padding.
     * @param string|null $prefix               Number prefix string, e.g. "RE-", "PR-", "CLX-".
     * @param string|null $suffix               Optional suffix appended after the number.
     * @param int|null    $validFromDate        Start of validity period in epoch milliseconds, or null for no limit.
     * @param int|null    $validToDate          End of validity period in epoch milliseconds, or null for no limit.
     * @param list<string> $salesInvoiceTypes    salesInvoiceType values this range applies to (may be empty).
     * @param list<string> $creditNoteInvoiceTypes salesInvoiceType values for credit notes in this range.
     * @param list<array>  $salesChannels        Distribution channel restrictions (raw, may be empty).
     * @param list<array>  $articleCategories    Article category restrictions (raw onlyId objects, may be empty).
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $numberRangeId,
        public readonly int     $interval,
        public readonly int     $lastValue,
        public readonly ?int    $length,
        public readonly ?string $prefix,
        public readonly ?string $suffix,
        public readonly ?int    $validFromDate,
        public readonly ?int    $validToDate,
        public readonly array   $salesInvoiceTypes,
        public readonly array   $creditNoteInvoiceTypes,
        public readonly array   $salesChannels,
        public readonly array   $articleCategories,
    ) {}

    /**
     * Create a NumberRangeValueDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                   self::str($data, 'id'),
            version:              self::str($data, 'version'),
            createdDate:          self::int($data, 'createdDate'),
            lastModifiedDate:     self::int($data, 'lastModifiedDate'),
            numberRangeId:        self::str($data, 'numberRangeId'),
            interval:             self::int($data, 'interval', 1),
            lastValue:            self::int($data, 'lastValue'),
            length:               self::intOrNull($data, 'length'),
            prefix:               self::strOrNull($data, 'prefix'),
            suffix:               self::strOrNull($data, 'suffix'),
            validFromDate:        self::intOrNull($data, 'validFromDate'),
            validToDate:          self::intOrNull($data, 'validToDate'),
            salesInvoiceTypes:    array_values(array_filter(
                array_map('strval', self::arr($data, 'salesInvoiceTypes')),
            )),
            creditNoteInvoiceTypes: array_values(array_filter(
                array_map('strval', self::arr($data, 'creditNoteInvoiceTypes')),
            )),
            salesChannels:        self::arr($data, 'salesChannels'),
            articleCategories:    self::arr($data, 'articleCategories'),
        );
    }

    /**
     * Returns true if this value is currently active (validity period includes today).
     *
     * A value with no validFromDate / validToDate is always active.
     * Values with an expired validToDate or a future validFromDate are inactive.
     */
    public function isCurrentlyActive(): bool
    {
        $nowMs    = (int) (microtime(true) * 1000);
        $fromOk   = $this->validFromDate === null || $this->validFromDate <= $nowMs;
        $toOk     = $this->validToDate   === null || $this->validToDate   >= $nowMs;

        return $fromOk && $toOk;
    }

    /**
     * Returns the validity start date as a DateTimeImmutable object.
     */
    public function getValidFrom(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['validFromDate' => $this->validFromDate], 'validFromDate');
    }

    /**
     * Returns the validity end date as a DateTimeImmutable object.
     */
    public function getValidTo(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['validToDate' => $this->validToDate], 'validToDate');
    }

    /**
     * Returns the creation date as a DateTimeImmutable object.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['createdDate' => $this->createdDate], 'createdDate');
    }

    /**
     * Formats the next number this range would issue, based on lastValue + interval.
     *
     * Returns null if no prefix is configured. The numeric part is zero-padded
     * to $length digits when $length is set.
     *
     * @example "PR-0042", "RE-10043"
     */
    public function formatNextNumber(): ?string
    {
        $next   = $this->lastValue + $this->interval;
        $numStr = $this->length !== null
            ? str_pad((string) $next, $this->length, '0', STR_PAD_LEFT)
            : (string) $next;

        return ($this->prefix ?? '') . $numStr . ($this->suffix ?? '');
    }
}
