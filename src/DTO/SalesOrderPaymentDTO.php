<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a payment condition / instalment entry on a sales order.
 *
 * Maps to the `salesOrderPayment` schema. Embedded inside
 * SalesOrderDTO::$payments. Tracks payment conditions, due dates and
 * whether the condition has been met.
 */
final class SalesOrderPaymentDTO extends AbstractDTO
{
    /**
     * @param string      $id               Internal weclapp UUID (readOnly).
     * @param string      $version          Optimistic locking version string (readOnly).
     * @param int         $createdDate      Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null $amount           Payment amount as a decimal string.
     * @param string|null $condition        Payment condition identifier.
     * @param bool        $conditionMet     True if the payment condition has been met.
     * @param int|null    $dueDate          Payment due date in epoch milliseconds.
     * @param int         $positionNumber   Position within the payment schedule (readOnly).
     * @param string|null $salesInvoiceId   ID of the linked sales invoice (readOnly).
     * @param array       $salesInvoices    List of linked sales invoice references [{id}].
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $amount,
        public readonly ?string $condition,
        public readonly bool    $conditionMet,
        public readonly ?int    $dueDate,
        public readonly int     $positionNumber,
        public readonly ?string $salesInvoiceId,
        public readonly array   $salesInvoices,
    ) {}

    /**
     * Create a SalesOrderPaymentDTO from a raw weclapp API response array.
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
            amount:           self::strOrNull($data, 'amount'),
            condition:        self::strOrNull($data, 'condition'),
            conditionMet:     self::bool($data, 'conditionMet'),
            dueDate:          self::intOrNull($data, 'dueDate'),
            positionNumber:   self::int($data, 'positionNumber'),
            salesInvoiceId:   self::strOrNull($data, 'salesInvoiceId'),
            salesInvoices:    self::arr($data, 'salesInvoices'),
        );
    }

    /**
     * Returns the payment amount as a float, or null if not set.
     */
    public function getAmount(): ?float
    {
        return $this->amount !== null ? (float) $this->amount : null;
    }

    /**
     * Returns the due date as a DateTimeImmutable object, or null if not set.
     */
    public function getDueDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['dueDate' => $this->dueDate], 'dueDate');
    }
}
