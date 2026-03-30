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
 * This DTO covers all invoice types, including Stornorechnungen (credit notes).
 * Use the salesInvoiceType field to distinguish between types:
 *   - STANDARD_INVOICE → regular invoice (RE-number range)
 *   - CREDIT_NOTE      → Stornorechnung (CLX-number range)
 *
 * To fetch only credit notes use SalesInvoiceResource::findCreditNotes().
 *
 * @see \miralsoft\weclapp\api\Resource\SalesInvoiceResource
 * @see \miralsoft\weclapp\api\Enum\SalesInvoiceType
 * @see \miralsoft\weclapp\api\Enum\SalesInvoiceStatus
 */
final class SalesInvoiceDTO extends AbstractDTO
{
    /**
     * @param string       $id                       Internal weclapp UUID.
     * @param string       $version                  Optimistic locking version string.
     * @param int          $createdDate              Creation timestamp in epoch milliseconds.
     * @param int          $lastModifiedDate         Last modification timestamp in epoch milliseconds.
     * @param string       $invoiceNumber            Human-readable invoice number (e.g. "RE-10042" or "CLX-1061").
     * @param string       $status                   Invoice status. See SalesInvoiceStatus enum.
     * @param string       $salesInvoiceType         Invoice type. See SalesInvoiceType enum.
     *                                               CREDIT_NOTE identifies a Stornorechnung (CLX-number range).
     * @param string       $customerId               ID of the linked customer.
     * @param string|null  $customerNumber           Human-readable customer number (e.g. "K-10042").
     * @param string|null  $partyId                  ID of the underlying party record (use with party endpoint).
     * @param string|null  $customerName             Customer display name (denormalised, not always returned by API).
     * @param int          $invoiceDate              Invoice date in epoch milliseconds.
     * @param int|null     $dueDate                  Payment due date in epoch milliseconds.
     * @param int|null     $bookingDate              Accounting booking date in epoch milliseconds.
     * @param string|null  $paymentMethodId          ID of the assigned payment method.
     * @param string|null  $paymentStatus            Payment status (e.g. "OPEN", "PAID", "CLEARED_WITH_CREDIT_NOTE").
     * @param bool         $paid                     True if the invoice has been fully paid.
     * @param float|null   $netAmount                Net invoice amount.
     * @param float|null   $grossAmount              Gross invoice amount (including tax).
     * @param float|null   $openAmount               Remaining unpaid amount.
     * @param string|null  $currency                 Currency code (e.g. "EUR").
     * @param string|null  $salesOrderId             ID of the originating sales order (if any).
     * @param string|null  $precedingSalesInvoiceId  For CREDIT_NOTE: ID of the original invoice being cancelled.
     *                                               Null for regular invoices.
     * @param string|null  $cancellationNumber       For cancelled invoices: the CLX-number of the credit note
     *                                               that was created. Null if not cancelled.
     * @param list<array>  $invoiceItems             Line items of this invoice.
     * @param list<array>  $tags                     List of tag objects.
     * @param list<array>  $customAttributes         List of custom attribute objects.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $invoiceNumber,
        public readonly string  $status,
        public readonly string  $salesInvoiceType,
        public readonly string  $customerId,
        public readonly ?string $customerNumber,
        public readonly ?string $partyId,
        public readonly ?string $customerName,
        public readonly int     $invoiceDate,
        public readonly ?int    $dueDate,
        public readonly ?int    $bookingDate,
        public readonly ?string $paymentMethodId,
        public readonly ?string $paymentStatus,
        public readonly bool    $paid,
        public readonly ?float  $netAmount,
        public readonly ?float  $grossAmount,
        public readonly ?float  $openAmount,
        public readonly ?string $currency,
        public readonly ?string $salesOrderId,
        public readonly ?string $precedingSalesInvoiceId,
        public readonly ?string $cancellationNumber,
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
            id:                      self::str($data, 'id'),
            version:                 self::str($data, 'version'),
            createdDate:             self::int($data, 'createdDate'),
            lastModifiedDate:        self::int($data, 'lastModifiedDate'),
            invoiceNumber:           self::str($data, 'invoiceNumber'),
            status:                  self::str($data, 'status'),
            salesInvoiceType:        self::str($data, 'salesInvoiceType'),
            customerId:              self::str($data, 'customerId'),
            customerNumber:          self::strOrNull($data, 'customerNumber'),
            partyId:                 self::strOrNull($data, 'partyId'),
            customerName:            self::strOrNull($data, 'customerName'),
            invoiceDate:             self::int($data, 'invoiceDate'),
            dueDate:                 self::intOrNull($data, 'dueDate'),
            bookingDate:             self::intOrNull($data, 'bookingDate'),
            paymentMethodId:         self::strOrNull($data, 'paymentMethodId'),
            paymentStatus:           self::strOrNull($data, 'paymentStatus'),
            paid:                    self::bool($data, 'paid'),
            netAmount:               self::floatOrNull($data, 'netAmount'),
            grossAmount:             self::floatOrNull($data, 'grossAmount'),
            openAmount:              self::floatOrNull($data, 'openAmount'),
            currency:                self::strOrNull($data, 'currency'),
            salesOrderId:            self::strOrNull($data, 'salesOrderId'),
            precedingSalesInvoiceId: self::strOrNull($data, 'precedingSalesInvoiceId'),
            cancellationNumber:      self::strOrNull($data, 'cancellationNumber'),
            invoiceItems:            self::arr($data, 'salesInvoiceItems'),
            tags:                    self::arr($data, 'tags'),
            customAttributes:        self::arr($data, 'customAttributes'),
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
     * Returns the accounting booking date as a DateTimeImmutable object.
     */
    public function getBookingDate(): ?DateTimeImmutable
    {
        if ($this->bookingDate === null) {
            return null;
        }

        return self::dateFromEpochMs(['bookingDate' => $this->bookingDate], 'bookingDate');
    }

    /**
     * Returns true if this invoice is a credit note (Stornorechnung).
     *
     * Credit notes carry a CLX-prefixed invoiceNumber and have their
     * precedingSalesInvoiceId set to the ID of the original invoice.
     *
     * @example
     * if ($invoice->isCreditNote()) {
     *     echo 'Stornorechnung: ' . $invoice->invoiceNumber;
     * }
     */
    public function isCreditNote(): bool
    {
        return $this->salesInvoiceType === 'CREDIT_NOTE';
    }

    /**
     * Returns the best available customer display name from inline invoice data.
     *
     * Uses the denormalised customerName field if the API returned it.
     * Falls back to the customerNumber, then to 'Unknown'.
     *
     * Note: This method cannot distinguish between ORGANIZATION and PERSON types
     * because the invoice payload does not carry name details. To get a properly
     * resolved display name (company name vs. first/last name), use
     * SalesInvoiceResource::resolveCustomerDisplayName() instead.
     */
    public function getCustomerDisplayName(): string
    {
        return $this->customerName
            ?? $this->customerNumber
            ?? 'Unknown';
    }

    /**
     * Returns true if the invoice has an outstanding open amount.
     */
    public function isOpen(): bool
    {
        return ($this->openAmount ?? 0.0) > 0.0;
    }
}
