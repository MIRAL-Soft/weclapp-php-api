<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Supplier record from the weclapp API.
 *
 * Suppliers map to the /api/v2/supplier endpoint.
 *
 * @see \miralsoft\weclapp\api\Resource\SupplierResource
 */
final class SupplierDTO extends AbstractDTO
{
    /**
     * @param string      $id                      Internal weclapp UUID.
     * @param string      $version                 Optimistic locking version string.
     * @param int         $createdDate             Creation timestamp in epoch milliseconds.
     * @param int         $lastModifiedDate        Last modification timestamp in epoch milliseconds.
     * @param string      $supplierNumber          Human-readable supplier number (e.g. "L-10001").
     * @param string      $partyType               Entity type: "ORGANIZATION" or "PERSON".
     * @param string      $company                 Company name.
     * @param string|null $salutation              Salutation.
     * @param string|null $firstName               First name (for PERSON type).
     * @param string|null $lastName                Last name (for PERSON type).
     * @param string|null $email                   Primary e-mail address.
     * @param string|null $phone                   Phone number.
     * @param string|null $mobile                  Mobile phone number.
     * @param string|null $website                 Website URL.
     * @param bool        $active                  Whether the supplier is active.
     * @param bool        $blocked                 Whether the supplier is blocked.
     * @param string|null $vatRegistrationNumber   VAT / UID number.
     * @param string|null $currencyId              Assigned currency ID.
     * @param string|null $currencyName            Assigned currency name.
     * @param string|null $paymentTermId           Assigned payment term ID.
     * @param string|null $deliveryTermId          Assigned delivery term ID.
     * @param string|null $responsibleUserId       ID of the responsible weclapp user.
     * @param list<array> $addresses               List of address objects.
     * @param list<array> $tags                    List of tag objects.
     * @param list<array> $customAttributes        List of custom attribute objects.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $supplierNumber,
        public readonly string  $partyType,
        public readonly string  $company,
        public readonly ?string $salutation,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $mobile,
        public readonly ?string $website,
        public readonly bool    $active,
        public readonly bool    $blocked,
        public readonly ?string $vatRegistrationNumber,
        public readonly ?string $currencyId,
        public readonly ?string $currencyName,
        public readonly ?string $paymentTermId,
        public readonly ?string $deliveryTermId,
        public readonly ?string $responsibleUserId,
        public readonly array   $addresses,
        public readonly array   $tags,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a SupplierDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                    self::str($data, 'id'),
            version:               self::str($data, 'version'),
            createdDate:           self::int($data, 'createdDate'),
            lastModifiedDate:      self::int($data, 'lastModifiedDate'),
            supplierNumber:        self::str($data, 'supplierNumber'),
            partyType:             self::str($data, 'partyType'),
            company:               self::str($data, 'company'),
            salutation:            self::strOrNull($data, 'salutation'),
            firstName:             self::strOrNull($data, 'firstName'),
            lastName:              self::strOrNull($data, 'lastName'),
            email:                 self::strOrNull($data, 'email'),
            phone:                 self::strOrNull($data, 'phone'),
            mobile:                self::strOrNull($data, 'mobile'),
            website:               self::strOrNull($data, 'website'),
            active:                self::bool($data, 'active', true),
            blocked:               self::bool($data, 'blocked'),
            vatRegistrationNumber: self::strOrNull($data, 'vatRegistrationNumber'),
            currencyId:            self::strOrNull($data, 'currencyId'),
            currencyName:          self::strOrNull($data, 'currencyName'),
            paymentTermId:         self::strOrNull($data, 'paymentTermId'),
            deliveryTermId:        self::strOrNull($data, 'deliveryTermId'),
            responsibleUserId:     self::strOrNull($data, 'responsibleUserId'),
            addresses:             self::arr($data, 'addresses'),
            tags:                  self::arr($data, 'tags'),
            customAttributes:      self::arr($data, 'customAttributes'),
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
     * Returns the display name of the supplier.
     */
    public function getDisplayName(): string
    {
        if ($this->company !== '') {
            return $this->company;
        }

        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }
}
