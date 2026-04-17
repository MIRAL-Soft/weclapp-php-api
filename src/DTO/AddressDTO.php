<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents an address record from the weclapp API.
 *
 * Maps to the `address` schema. Used as a nested object on sales orders,
 * sales invoices, quotations and other documents for deliveryAddress,
 * invoiceAddress and recordAddress fields.
 */
final class AddressDTO extends AbstractDTO
{
    /**
     * @param string      $id                    Internal weclapp UUID (readOnly).
     * @param string      $version               Optimistic locking version string (readOnly).
     * @param int         $createdDate           Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate      Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null $city                  City name.
     * @param string|null $company               Primary company name.
     * @param string|null $company2              Secondary company name / department.
     * @param string|null $countryCode           ISO 3166-1 alpha-2 country code (e.g. "DE").
     * @param bool        $deliveryAddress       True if this address is marked as a delivery address.
     * @param string|null $firstName             First name of the contact person.
     * @param string|null $globalLocationNumber  GS1 Global Location Number.
     * @param bool        $invoiceAddress        True if this address is marked as an invoice address.
     * @param string|null $lastName              Last name of the contact person.
     * @param string|null $phoneNumber           Phone number.
     * @param string|null $postOfficeBoxCity     City for post office box address.
     * @param string|null $postOfficeBoxNumber   Post office box number.
     * @param string|null $postOfficeBoxZipCode  Zip code for post office box address.
     * @param bool        $primaryAddress        True if this is the primary address.
     * @param string|null $salutation            Salutation (e.g. "MR", "MRS").
     * @param string|null $state                 State or region.
     * @param string|null $street1               Primary street line.
     * @param string|null $street2               Secondary street line.
     * @param string|null $titleId               ID of the academic/professional title.
     * @param string|null $zipcode               Postal zip code.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $city,
        public readonly ?string $company,
        public readonly ?string $company2,
        public readonly ?string $countryCode,
        public readonly bool    $deliveryAddress,
        public readonly ?string $firstName,
        public readonly ?string $globalLocationNumber,
        public readonly bool    $invoiceAddress,
        public readonly ?string $lastName,
        public readonly ?string $phoneNumber,
        public readonly ?string $postOfficeBoxCity,
        public readonly ?string $postOfficeBoxNumber,
        public readonly ?string $postOfficeBoxZipCode,
        public readonly bool    $primaryAddress,
        public readonly ?string $salutation,
        public readonly ?string $state,
        public readonly ?string $street1,
        public readonly ?string $street2,
        public readonly ?string $titleId,
        public readonly ?string $zipcode,
    ) {}

    /**
     * Create an AddressDTO from a raw weclapp API response array.
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
            city:                 self::strOrNull($data, 'city'),
            company:              self::strOrNull($data, 'company'),
            company2:             self::strOrNull($data, 'company2'),
            countryCode:          self::strOrNull($data, 'countryCode'),
            deliveryAddress:      self::bool($data, 'deliveryAddress'),
            firstName:            self::strOrNull($data, 'firstName'),
            globalLocationNumber: self::strOrNull($data, 'globalLocationNumber'),
            invoiceAddress:       self::bool($data, 'invoiceAddress'),
            lastName:             self::strOrNull($data, 'lastName'),
            phoneNumber:          self::strOrNull($data, 'phoneNumber'),
            postOfficeBoxCity:    self::strOrNull($data, 'postOfficeBoxCity'),
            postOfficeBoxNumber:  self::strOrNull($data, 'postOfficeBoxNumber'),
            postOfficeBoxZipCode: self::strOrNull($data, 'postOfficeBoxZipCode'),
            primaryAddress:       self::bool($data, 'primaryAddress'),
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
        $parts = array_filter([
            $this->company ?? ($this->firstName . ' ' . $this->lastName),
            $this->street1,
            $this->zipcode . ' ' . $this->city,
            $this->countryCode,
        ]);

        return implode(', ', $parts);
    }
}
