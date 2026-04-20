<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents an online account (social media, web profile, etc.) linked to a party.
 *
 * Maps to the onlineAccount schema. Instances are embedded inside
 * the $onlineAccounts array of PartyDTO, CustomerDTO, ContactDTO, and SupplierDTO.
 * All 7 fields of the weclapp OpenAPI onlineAccount schema are covered.
 *
 * @see \miralsoft\weclapp\api\DTO\PartyDTO
 */
final class OnlineAccountDTO extends AbstractDTO
{
    /**
     * @param string       $id               Internal weclapp UUID (readOnly).
     * @param string       $version          Optimistic locking version string (readOnly).
     * @param int          $createdDate      Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $accountName      Display name / handle for the online account.
     * @param string|null  $accountType      Type of online account (enum: onlineAccountType).
     * @param string|null  $url              URL or profile link for the online account.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $accountName,
        public readonly ?string $accountType,
        public readonly ?string $url,
    ) {}

    /**
     * Create an OnlineAccountDTO from a raw weclapp API response array.
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
            accountName:      self::strOrNull($data, 'accountName'),
            accountType:      self::strOrNull($data, 'accountType'),
            url:              self::strOrNull($data, 'url'),
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
}
