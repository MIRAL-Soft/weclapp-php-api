<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents an email addresses configuration object in the weclapp API.
 *
 * Maps to the emailAddresses schema. Used in SalesOrderDTO, SalesInvoiceDTO,
 * and QuotationDTO for delivery, record, sales invoice, and sales order
 * email address overrides.
 *
 * All 3 fields of the weclapp OpenAPI emailAddresses schema are covered.
 * Note: this schema has no identity fields (no id/version/timestamps).
 *
 * @see \miralsoft\weclapp\api\DTO\SalesOrderDTO
 * @see \miralsoft\weclapp\api\DTO\SalesInvoiceDTO
 * @see \miralsoft\weclapp\api\DTO\QuotationDTO
 */
final class EmailAddressesDTO extends AbstractDTO
{
    /**
     * @param list<string> $bccAddresses List of BCC e-mail addresses.
     * @param list<string> $ccAddresses  List of CC e-mail addresses.
     * @param list<string> $toAddresses  List of TO (primary recipient) e-mail addresses.
     */
    public function __construct(
        public readonly array $bccAddresses,
        public readonly array $ccAddresses,
        public readonly array $toAddresses,
    ) {}

    /**
     * Create an EmailAddressesDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            bccAddresses: self::arr($data, 'bccAddresses'),
            ccAddresses:  self::arr($data, 'ccAddresses'),
            toAddresses:  self::arr($data, 'toAddresses'),
        );
    }

    /**
     * Returns all unique e-mail addresses across TO, CC, and BCC.
     *
     * @return list<string>
     */
    public function getAllAddresses(): array
    {
        return array_values(array_unique(array_merge(
            $this->toAddresses,
            $this->ccAddresses,
            $this->bccAddresses,
        )));
    }

    /**
     * Returns true if any address is configured in this object.
     */
    public function isEmpty(): bool
    {
        return empty($this->toAddresses) && empty($this->ccAddresses) && empty($this->bccAddresses);
    }
}
