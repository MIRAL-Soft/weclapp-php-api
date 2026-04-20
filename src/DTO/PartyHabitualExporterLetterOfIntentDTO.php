<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a habitual exporter letter of intent linked to a party.
 *
 * Maps to the partyHabitualExporterLetterOfIntent schema. Instances are embedded
 * inside the $partyHabitualExporterLettersOfIntent array of PartyDTO, CustomerDTO,
 * ContactDTO, and SupplierDTO.
 * All 12 fields of the weclapp OpenAPI partyHabitualExporterLetterOfIntent schema are covered.
 *
 * @see \miralsoft\weclapp\api\DTO\PartyDTO
 */
final class PartyHabitualExporterLetterOfIntentDTO extends AbstractDTO
{
    /**
     * @param string       $id                              Internal weclapp UUID (readOnly).
     * @param string       $version                         Optimistic locking version string (readOnly).
     * @param int          $createdDate                     Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate                Last modification timestamp in epoch milliseconds (readOnly).
     * @param bool         $automaticallySuggestInInvoice   Whether to automatically suggest this letter in invoice creation.
     * @param int|null     $date                            Date of the letter of intent in epoch milliseconds.
     * @param bool         $fromSupplier                    Whether this letter was received from a supplier.
     * @param array        $invoices                        List of linked invoice references (readOnly).
     * @param string|null  $numberDeclarer                  Declaration number from the declarer.
     * @param string|null  $numberSupplier                  Declaration number from the supplier.
     * @param string|null  $totalAmount                     Total covered amount as decimal string.
     * @param string|null  $type                            Type of habitual exporter letter (enum: partyHabitualExporterLetterOfIntentType).
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly bool    $automaticallySuggestInInvoice,
        public readonly ?int    $date,
        public readonly bool    $fromSupplier,
        public readonly array   $invoices,
        public readonly ?string $numberDeclarer,
        public readonly ?string $numberSupplier,
        public readonly ?string $totalAmount,
        public readonly ?string $type,
    ) {}

    /**
     * Create a PartyHabitualExporterLetterOfIntentDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                            self::str($data, 'id'),
            version:                       self::str($data, 'version'),
            createdDate:                   self::int($data, 'createdDate'),
            lastModifiedDate:              self::int($data, 'lastModifiedDate'),
            automaticallySuggestInInvoice: self::bool($data, 'automaticallySuggestInInvoice'),
            date:                          self::intOrNull($data, 'date'),
            fromSupplier:                  self::bool($data, 'fromSupplier'),
            invoices:                      self::arr($data, 'invoices'),
            numberDeclarer:                self::strOrNull($data, 'numberDeclarer'),
            numberSupplier:                self::strOrNull($data, 'numberSupplier'),
            totalAmount:                   self::strOrNull($data, 'totalAmount'),
            type:                          self::strOrNull($data, 'type'),
        );
    }

    /**
     * Returns the total covered amount as a float, or null if not set.
     */
    public function getTotalAmount(): ?float
    {
        return $this->totalAmount !== null ? (float) $this->totalAmount : null;
    }

    /**
     * Returns the date of the letter as a DateTimeImmutable object.
     */
    public function getDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['date' => $this->date], 'date');
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
