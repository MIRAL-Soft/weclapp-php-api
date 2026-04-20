<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents an email addresses configuration object in the weclapp API.
 *
 * Maps to the `emailAddresses` schema. Used in SalesOrderDTO, SalesInvoiceDTO,
 * and QuotationDTO for delivery, record, sales invoice, and sales order
 * email address overrides.
 *
 * All 3 fields are plain strings (as defined in the OpenAPI spec).
 * Multiple addresses are stored comma-separated within the string.
 * This schema has no identity fields (no id/version/timestamps).
 *
 * @see \miralsoft\weclapp\api\DTO\SalesOrderDTO
 * @see \miralsoft\weclapp\api\DTO\SalesInvoiceDTO
 * @see \miralsoft\weclapp\api\DTO\QuotationDTO
 */
final class EmailAddressesDTO extends AbstractDTO
{
    /**
     * @param string|null $bccAddresses BCC e-mail addresses (comma-separated).
     * @param string|null $ccAddresses  CC e-mail addresses (comma-separated).
     * @param string|null $toAddresses  Primary recipient e-mail addresses (comma-separated).
     */
    public function __construct(
        public readonly ?string $bccAddresses,
        public readonly ?string $ccAddresses,
        public readonly ?string $toAddresses,
    ) {}

    /**
     * Create an EmailAddressesDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            bccAddresses: self::strOrNull($data, 'bccAddresses'),
            ccAddresses:  self::strOrNull($data, 'ccAddresses'),
            toAddresses:  self::strOrNull($data, 'toAddresses'),
        );
    }

    /**
     * Returns all unique individual e-mail addresses across TO, CC, and BCC.
     *
     * Parses the comma-separated strings and returns a flat, deduplicated list.
     *
     * @return list<string>
     */
    public function getAllAddresses(): array
    {
        $parse = static fn(?string $v): array => ($v !== null && $v !== '')
            ? array_map('trim', explode(',', $v))
            : [];

        return array_values(array_unique(array_merge(
            $parse($this->toAddresses),
            $parse($this->ccAddresses),
            $parse($this->bccAddresses),
        )));
    }

    /**
     * Returns true if no e-mail address is configured in this object.
     */
    public function isEmpty(): bool
    {
        return ($this->toAddresses === null || $this->toAddresses === '')
            && ($this->ccAddresses === null || $this->ccAddresses === '')
            && ($this->bccAddresses === null || $this->bccAddresses === '');
    }
}
