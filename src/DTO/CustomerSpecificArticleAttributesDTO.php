<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a customer-specific article number/attribute mapping.
 *
 * Maps to the customerSpecificArticleAttributes schema in the weclapp API.
 * Instances are embedded inside ArticleDTO::$customerArticleNumbers.
 *
 * API key name on the parent article: customerArticleNumbers
 *
 * @see \miralsoft\weclapp\api\DTO\ArticleDTO
 */
final class CustomerSpecificArticleAttributesDTO extends AbstractDTO
{
    /**
     * @param string       $id                    Internal weclapp UUID (readOnly).
     * @param string       $version               Optimistic locking version string (readOnly).
     * @param int          $createdDate           Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate      Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $customerId            ID of the customer this mapping belongs to.
     * @param string|null  $customerArticleNumber Article number as used by this specific customer.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $customerId,
        public readonly ?string $customerArticleNumber,
    ) {}

    /**
     * Create a CustomerSpecificArticleAttributesDTO from a raw weclapp API response array.
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
            customerId:            self::strOrNull($data, 'customerId'),
            customerArticleNumber: self::strOrNull($data, 'customerArticleNumber'),
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
