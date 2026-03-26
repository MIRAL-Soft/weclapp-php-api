<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Customer record from the weclapp API.
 *
 * Customers are the primary business entities and map to the
 * /api/v2/customer endpoint. Each customer may have associated
 * contacts (ContactDTO) and addresses.
 *
 * The lastModifiedDate field is crucial for delta-sync integrations:
 * use QueryBuilder::modifiedSince() to retrieve only changed customers.
 *
 * @see \miralsoft\weclapp\api\Resource\CustomerResource
 */
final class CustomerDTO extends AbstractDTO
{
    /**
     * @param string               $id                      Internal weclapp UUID.
     * @param string               $version                 Optimistic locking version string.
     * @param int                  $createdDate             Creation timestamp in epoch milliseconds.
     * @param int                  $lastModifiedDate        Last modification timestamp in epoch milliseconds.
     * @param string               $customerNumber          Human-readable customer number (e.g. "K-10042").
     * @param string               $partyType               Entity type: "ORGANIZATION" or "PERSON".
     * @param string               $company                 Company name (for ORGANIZATION type).
     * @param string|null          $salutation              Salutation (e.g. "MR", "MRS").
     * @param string|null          $title                   Academic or professional title.
     * @param string|null          $firstName               First name (for PERSON type).
     * @param string|null          $lastName                Last name (for PERSON type).
     * @param string|null          $email                   Primary e-mail address.
     * @param string|null          $phone                   Primary phone number.
     * @param string|null          $mobile                  Mobile phone number.
     * @param string|null          $fax                     Fax number.
     * @param string|null          $website                 Website URL.
     * @param bool                 $active                  Whether the customer is active.
     * @param bool                 $blocked                 Whether the customer is blocked from ordering.
     * @param bool                 $insolvent               Whether insolvency proceedings are active.
     * @param string|null          $vatRegistrationNumber   VAT / UID number.
     * @param string|null          $currencyId              Assigned currency ID.
     * @param string|null          $currencyName            Assigned currency name (e.g. "EUR").
     * @param string|null          $paymentTermId           Assigned payment term ID.
     * @param string|null          $deliveryTermId          Assigned delivery term ID.
     * @param string|null          $responsibleUserId       ID of the responsible weclapp user.
     * @param string|null          $responsibleUserUsername Username of the responsible user.
     * @param string|null          $salesChannel            Assigned sales channel.
     * @param list<array>          $addresses               List of address objects.
     * @param list<array>          $contacts                List of linked contact references.
     * @param list<array>          $tags                    List of tag objects.
     * @param list<array>          $customAttributes        List of custom attribute objects.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $customerNumber,
        public readonly string  $partyType,
        public readonly string  $company,
        public readonly ?string $salutation,
        public readonly ?string $title,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $mobile,
        public readonly ?string $fax,
        public readonly ?string $website,
        public readonly bool    $active,
        public readonly bool    $blocked,
        public readonly bool    $insolvent,
        public readonly ?string $vatRegistrationNumber,
        public readonly ?string $currencyId,
        public readonly ?string $currencyName,
        public readonly ?string $paymentTermId,
        public readonly ?string $deliveryTermId,
        public readonly ?string $responsibleUserId,
        public readonly ?string $responsibleUserUsername,
        public readonly ?string $salesChannel,
        public readonly array   $addresses,
        public readonly array   $contacts,
        public readonly array   $tags,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a CustomerDTO from a raw weclapp API response array.
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
            customerNumber:          self::str($data, 'customerNumber'),
            partyType:               self::str($data, 'partyType'),
            company:                 self::str($data, 'company'),
            salutation:              self::strOrNull($data, 'salutation'),
            title:                   self::strOrNull($data, 'title'),
            firstName:               self::strOrNull($data, 'firstName'),
            lastName:                self::strOrNull($data, 'lastName'),
            email:                   self::strOrNull($data, 'email'),
            phone:                   self::strOrNull($data, 'phone'),
            mobile:                  self::strOrNull($data, 'mobile'),
            fax:                     self::strOrNull($data, 'fax'),
            website:                 self::strOrNull($data, 'website'),
            active:                  self::bool($data, 'active', true),
            blocked:                 self::bool($data, 'blocked'),
            insolvent:               self::bool($data, 'insolvent'),
            vatRegistrationNumber:   self::strOrNull($data, 'vatRegistrationNumber'),
            currencyId:              self::strOrNull($data, 'currencyId'),
            currencyName:            self::strOrNull($data, 'currencyName'),
            paymentTermId:           self::strOrNull($data, 'paymentTermId'),
            deliveryTermId:          self::strOrNull($data, 'deliveryTermId'),
            responsibleUserId:       self::strOrNull($data, 'responsibleUserId'),
            responsibleUserUsername: self::strOrNull($data, 'responsibleUserUsername'),
            salesChannel:            self::strOrNull($data, 'salesChannel'),
            addresses:               self::arr($data, 'addresses'),
            contacts:                self::arr($data, 'contacts'),
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
     *
     * Use this for delta-sync: compare against your last sync timestamp
     * to determine if this record needs to be updated in an external system.
     */
    public function getLastModifiedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['lastModifiedDate' => $this->lastModifiedDate], 'lastModifiedDate');
    }

    /**
     * Returns the display name of the customer.
     *
     * For ORGANIZATION type returns the company name.
     * For PERSON type returns "firstName lastName".
     */
    public function getDisplayName(): string
    {
        if ($this->company !== '') {
            return $this->company;
        }

        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }
}
