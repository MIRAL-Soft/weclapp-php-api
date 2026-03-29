<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Party record from the weclapp API.
 *
 * The party endpoint is the common base for customers, suppliers and contacts.
 * Use this when you only need identity data (number, name) without the full
 * customer or supplier payload — e.g. to resolve a partyId on an invoice.
 *
 * @see \miralsoft\weclapp\api\Resource\PartyResource
 */
final class PartyDTO extends AbstractDTO
{
    /**
     * @param string       $id               Internal weclapp UUID.
     * @param string       $version          Optimistic locking version string.
     * @param int          $createdDate      Creation timestamp in epoch milliseconds.
     * @param int          $lastModifiedDate Last modification timestamp in epoch milliseconds.
     * @param string       $partyType        Entity type: "ORGANIZATION" or "PERSON".
     * @param string|null  $customerNumber   Human-readable customer number (e.g. "K-10042").
     * @param string|null  $company          Company name (for ORGANIZATION type).
     * @param string|null  $firstName        First name (for PERSON type).
     * @param string|null  $lastName         Last name (for PERSON type).
     * @param string|null  $email            Primary e-mail address.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $partyType,
        public readonly ?string $customerNumber,
        public readonly ?string $company,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
    ) {}

    /**
     * Create a PartyDTO from a raw weclapp API response array.
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
            partyType:        self::str($data, 'partyType'),
            customerNumber:   self::strOrNull($data, 'customerNumber'),
            company:          self::strOrNull($data, 'company'),
            firstName:        self::strOrNull($data, 'firstName'),
            lastName:         self::strOrNull($data, 'lastName'),
            email:            self::strOrNull($data, 'email'),
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
     * Returns the display name of the party.
     *
     * For ORGANIZATION type returns the company name.
     * For PERSON type returns "firstName lastName".
     */
    public function getDisplayName(): string
    {
        if ($this->company !== null && $this->company !== '') {
            return $this->company;
        }

        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }
}
