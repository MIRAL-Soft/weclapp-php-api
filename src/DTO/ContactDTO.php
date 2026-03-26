<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Contact record from the weclapp API.
 *
 * Contacts are persons linked to a customer organisation.
 * They map to the /api/v2/contact endpoint.
 *
 * For delta-sync with external systems (e.g. DocBee), use
 * QueryBuilder::modifiedSince() combined with the lastModifiedDate field.
 *
 * @see \miralsoft\weclapp\api\Resource\ContactResource
 */
final class ContactDTO extends AbstractDTO
{
    /**
     * @param string      $id                  Internal weclapp UUID.
     * @param string      $version             Optimistic locking version string.
     * @param int         $createdDate         Creation timestamp in epoch milliseconds.
     * @param int         $lastModifiedDate    Last modification timestamp in epoch milliseconds.
     * @param string      $partyType           Always "PERSON" for contacts.
     * @param string|null $salutation          Salutation (e.g. "MR", "MRS").
     * @param string|null $title               Academic or professional title.
     * @param string|null $firstName           First name.
     * @param string|null $lastName            Last name.
     * @param string|null $email               Primary e-mail address.
     * @param string|null $phone               Phone number.
     * @param string|null $mobile              Mobile phone number.
     * @param string|null $fax                 Fax number.
     * @param string|null $position            Job title or position within the company.
     * @param string|null $department          Department within the company.
     * @param bool        $active              Whether the contact is active.
     * @param string|null $customerId          ID of the linked customer (parent organisation).
     * @param string|null $personCompany       Name of the company this person belongs to.
     * @param string|null $responsibleUserId   ID of the responsible weclapp user.
     * @param list<array> $addresses           List of address objects.
     * @param list<array> $tags                List of tag objects.
     * @param list<array> $customAttributes    List of custom attribute objects.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $partyType,
        public readonly ?string $salutation,
        public readonly ?string $title,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $mobile,
        public readonly ?string $fax,
        public readonly ?string $position,
        public readonly ?string $department,
        public readonly bool    $active,
        public readonly ?string $customerId,
        public readonly ?string $personCompany,
        public readonly ?string $responsibleUserId,
        public readonly array   $addresses,
        public readonly array   $tags,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a ContactDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                self::str($data, 'id'),
            version:           self::str($data, 'version'),
            createdDate:       self::int($data, 'createdDate'),
            lastModifiedDate:  self::int($data, 'lastModifiedDate'),
            partyType:         self::str($data, 'partyType', 'PERSON'),
            salutation:        self::strOrNull($data, 'salutation'),
            title:             self::strOrNull($data, 'title'),
            firstName:         self::strOrNull($data, 'firstName'),
            lastName:          self::strOrNull($data, 'lastName'),
            email:             self::strOrNull($data, 'email'),
            phone:             self::strOrNull($data, 'phone'),
            mobile:            self::strOrNull($data, 'mobile'),
            fax:               self::strOrNull($data, 'fax'),
            position:          self::strOrNull($data, 'position'),
            department:        self::strOrNull($data, 'department'),
            active:            self::bool($data, 'active', true),
            customerId:        self::strOrNull($data, 'customerId'),
            personCompany:     self::strOrNull($data, 'personCompany'),
            responsibleUserId: self::strOrNull($data, 'responsibleUserId'),
            addresses:         self::arr($data, 'addresses'),
            tags:              self::arr($data, 'tags'),
            customAttributes:  self::arr($data, 'customAttributes'),
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
     * Returns the full display name of the contact.
     */
    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }
}
