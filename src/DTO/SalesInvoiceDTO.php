<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Sales Invoice from the weclapp API.
 *
 * Sales invoices map to the /api/v2/salesInvoice endpoint.
 * PDF downloads are available via SalesInvoiceResource::getPdf().
 *
 * @see \miralsoft\weclapp\api\Resource\SalesInvoiceResource
 */
final class SalesInvoiceDTO extends AbstractDTO
{
    /**
     * @param string       $id                 Internal weclapp UUID.
     * @param string       $version            Optimistic locking version string.
     * @param int          $createdDate        Creation timestamp in epoch milliseconds.
     * @param int          $lastModifiedDate   Last modification timestamp in epoch milliseconds.
     * @param string       $invoiceNumber      Human-readable invoice number (e.g. "RE-10042").
     * @param string       $status             Invoice status (e.g. "INVOICE_DRAFT", "INVOICE_SENT").
     * @param string       $customerId         ID of the linked customer.
     * @param string|null  $customerName       Customer display name (denormalised).
     * @param int          $invoiceDate        Invoice date in epoch milliseconds.
     * @param int|null     $dueDate            Payment due date in epoch milliseconds.
     * @param string|null  $paymentMethodId    ID of the assigned payment method.
     * @param float|null   $netAmount          Net invoice amount.
     * @param float|null   $grossAmount        Gross invoice amount (including tax).
     * @param float|null   $openAmount         Remaining unpaid amount.
     * @param string|null  $currency           Currency code (e.g. "EUR").
     * @param string|null  $salesOrderId       ID of the originating sales order (if any).
     * @param list<array>  $invoiceItems       Line items of this invoice.
     * @param list<array>  $tags               List of tag objects.
     * @param list<array>  $customAttributes   List of custom attribute objects.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $invoiceNumber,
        public readonly string  $status,
        public readonly string  $customerId,
        public readonly ?string $customerName,
        public readonly int     $invoiceDate,
        public readonly ?int    $dueDate,
        public readonly ?string $paymentMethodId,
        public readonly ?float  $netAmount,
        public readonly ?float  $grossAmount,
        public readonly ?float  $openAmount,
        public readonly ?string $currency,
        public readonly ?string $salesOrderId,
        public readonly array   $invoiceItems,
        public readonly array   $tags,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a SalesInvoiceDTO from a raw weclapp API response array.
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
            invoiceNumber:    self::str($data, 'invoiceNumber'),
            status:           self::str($data, 'status'),
            customerId:       self::str($data, 'customerId'),
            customerName:     self::strOrNull($data, 'customerName'),
            invoiceDate:      self::int($data, 'invoiceDate'),
            dueDate:          self::intOrNull($data, 'dueDate'),
            paymentMethodId:  self::strOrNull($data, 'paymentMethodId'),
            netAmount:        self::floatOrNull($data, 'netAmount'),
            grossAmount:      self::floatOrNull($data, 'grossAmount'),
            openAmount:       self::floatOrNull($data, 'openAmount'),
            currency:         self::strOrNull($data, 'currency'),
            salesOrderId:     self::strOrNull($data, 'salesOrderId'),
            invoiceItems:     self::arr($data, 'invoiceItems'),
            tags:             self::arr($data, 'tags'),
            customAttributes: self::arr($data, 'customAttributes'),
        );
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

    /**
     * Returns the invoice date as a DateTimeImmutable object.
     */
    public function getInvoiceDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['invoiceDate' => $this->invoiceDate], 'invoiceDate');
    }

    /**
     * Returns the due date as a DateTimeImmutable object.
     */
    public function getDueDate(): ?DateTimeImmutable
    {
        if ($this->dueDate === null) {
            return null;
        }

        return self::dateFromEpochMs(['dueDate' => $this->dueDate], 'dueDate');
    }

    /**
     * Returns true if the invoice has an outstanding open amount.
     */
    public function isOpen(): bool
    {
        return ($this->openAmount ?? 0.0) > 0.0;
    }
}
