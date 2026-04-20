<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents an embedded address on a weclapp sales document.
 *
 * Maps to the `recordAddress` schema used for deliveryAddress, invoiceAddress
 * and recordAddress fields on SalesOrderDTO, SalesInvoiceDTO and QuotationDTO
 * (and similarly on PurchaseOrderDTO and ShipmentDTO).
 *
 * Unlike AddressDTO (which maps to the `address` schema used in
 * PartyDTO::$addresses), this schema has NO identity fields (no id, version,
 * createdDate, lastModifiedDate). It adds `middleName` and omits the boolean
 * flags deliveryAddress, invoiceAddress and primaryAddress.
 *
 * All 18 fields are nullable strings — none are required by the API schema.
 *
 * @see \miralsoft\weclapp\api\DTO\AddressDTO  For the party address schema (24 fields, with identity).
 */
final class RecordAddressDTO extends AbstractDTO
{
    /**
     * @param string|null $city                  City name.
     * @param string|null $company               Primary company name.
     * @param string|null $company2              Secondary company name / department.
     * @param string|null $countryCode           ISO 3166-1 alpha-2 country code (e.g. "DE").
     * @param string|null $firstName             First name of the contact person.
     * @param string|null $globalLocationNumber  GS1 Global Location Number.
     * @param string|null $lastName              Last name of the contact person.
     * @param string|null $middleName            Middle name of the contact person.
     * @param string|null $phoneNumber           Phone number.
     * @param string|null $postOfficeBoxCity     City for post office box address.
     * @param string|null $postOfficeBoxNumber   Post office box number.
     * @param string|null $postOfficeBoxZipCode  Zip code for post office box address.
     * @param string|null $salutation            Salutation (e.g. "MR", "MRS").
     * @param string|null $state                 State or region.
     * @param string|null $street1               Primary street line.
     * @param string|null $street2               Secondary street line.
     * @param string|null $titleId               ID of the academic/professional title.
     * @param string|null $zipcode               Postal zip code.
     */
    public function __construct(
        public readonly ?string $city,
        public readonly ?string $company,
        public readonly ?string $company2,
        public readonly ?string $countryCode,
        public readonly ?string $firstName,
        public readonly ?string $globalLocationNumber,
        public readonly ?string $lastName,
        public readonly ?string $middleName,
        public readonly ?string $phoneNumber,
        public readonly ?string $postOfficeBoxCity,
        public readonly ?string $postOfficeBoxNumber,
        public readonly ?string $postOfficeBoxZipCode,
        public readonly ?string $salutation,
        public readonly ?string $state,
        public readonly ?string $street1,
        public readonly ?string $street2,
        public readonly ?string $titleId,
        public readonly ?string $zipcode,
    ) {}

    /**
     * Create a RecordAddressDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            city:                 self::strOrNull($data, 'city'),
            company:              self::strOrNull($data, 'company'),
            company2:             self::strOrNull($data, 'company2'),
            countryCode:          self::strOrNull($data, 'countryCode'),
            firstName:            self::strOrNull($data, 'firstName'),
            globalLocationNumber: self::strOrNull($data, 'globalLocationNumber'),
            lastName:             self::strOrNull($data, 'lastName'),
            middleName:           self::strOrNull($data, 'middleName'),
            phoneNumber:          self::strOrNull($data, 'phoneNumber'),
            postOfficeBoxCity:    self::strOrNull($data, 'postOfficeBoxCity'),
            postOfficeBoxNumber:  self::strOrNull($data, 'postOfficeBoxNumber'),
            postOfficeBoxZipCode: self::strOrNull($data, 'postOfficeBoxZipCode'),
            salutation:           self::strOrNull($data, 'salutation'),
            state:                self::strOrNull($data, 'state'),
            street1:              self::strOrNull($data, 'street1'),
            street2:              self::strOrNull($data, 'street2'),
            titleId:              self::strOrNull($data, 'titleId'),
            zipcode:              self::strOrNull($data, 'zipcode'),
        );
    }

    /**
     * Returns a single-line display representation of the address.
     */
    public function getDisplayLine(): string
    {
        $name = trim(implode(' ', array_filter([
            $this->firstName,
            $this->middleName,
            $this->lastName,
        ])));

        $parts = array_filter([
            $this->company ?? ($name ?: null),
            $this->street1,
            trim(($this->zipcode ?? '') . ' ' . ($this->city ?? '')) ?: null,
            $this->countryCode,
        ]);

        return implode(', ', $parts);
    }
}
