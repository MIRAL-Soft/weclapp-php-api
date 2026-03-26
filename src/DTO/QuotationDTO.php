<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Quotation (offer/Angebot) from the weclapp API.
 *
 * Quotations map to the /api/v2/quotation endpoint.
 * A quotation can be converted to a SalesOrder via QuotationResource::convertToSalesOrder().
 *
 * @see \miralsoft\weclapp\api\Resource\QuotationResource
 */
final class QuotationDTO extends AbstractDTO
{
    /**
     * @param string       $id                 Internal weclapp UUID.
     * @param string       $version            Optimistic locking version string.
     * @param int          $createdDate        Creation timestamp in epoch milliseconds.
     * @param int          $lastModifiedDate   Last modification timestamp in epoch milliseconds.
     * @param string       $quotationNumber    Human-readable quotation number (e.g. "ANG-10042").
     * @param string       $status             Quotation status (e.g. "QUOTATION_DRAFT", "QUOTATION_SENT").
     * @param string       $customerId         ID of the linked customer.
     * @param string|null  $customerName       Customer display name (denormalised).
     * @param int          $quotationDate      Quotation date in epoch milliseconds.
     * @param int|null     $validUntilDate     Expiry date in epoch milliseconds.
     * @param string|null  $description        Internal description / comment.
     * @param float|null   $netAmount          Net quotation amount.
     * @param float|null   $grossAmount        Gross quotation amount (including tax).
     * @param string|null  $currency           Currency code (e.g. "EUR").
     * @param string|null  $responsibleUserId  ID of the responsible weclapp user.
     * @param list<array>  $quotationItems     Line items of this quotation.
     * @param list<array>  $tags               List of tag objects.
     * @param list<array>  $customAttributes   List of custom attribute objects.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $quotationNumber,
        public readonly string  $status,
        public readonly string  $customerId,
        public readonly ?string $customerName,
        public readonly int     $quotationDate,
        public readonly ?int    $validUntilDate,
        public readonly ?string $description,
        public readonly ?float  $netAmount,
        public readonly ?float  $grossAmount,
        public readonly ?string $currency,
        public readonly ?string $responsibleUserId,
        public readonly array   $quotationItems,
        public readonly array   $tags,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a QuotationDTO from a raw weclapp API response array.
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
            quotationNumber:   self::str($data, 'quotationNumber'),
            status:            self::str($data, 'status'),
            customerId:        self::str($data, 'customerId'),
            customerName:      self::strOrNull($data, 'customerName'),
            quotationDate:     self::int($data, 'quotationDate'),
            validUntilDate:    self::intOrNull($data, 'validUntilDate'),
            description:       self::strOrNull($data, 'description'),
            netAmount:         self::floatOrNull($data, 'netAmount'),
            grossAmount:       self::floatOrNull($data, 'grossAmount'),
            currency:          self::strOrNull($data, 'currency'),
            responsibleUserId: self::strOrNull($data, 'responsibleUserId'),
            quotationItems:    self::arr($data, 'quotationItems'),
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
     * Returns the valid-until date as a DateTimeImmutable object, or null if not set.
     */
    public function getValidUntil(): ?DateTimeImmutable
    {
        if ($this->validUntilDate === null) {
            return null;
        }

        return self::dateFromEpochMs(['validUntilDate' => $this->validUntilDate], 'validUntilDate');
    }

    /**
     * Returns true if the quotation has expired (validUntilDate is in the past).
     *
     * Compares as DateTimeImmutable objects to avoid integer overflow and to
     * make the intent explicit. Returns false if no expiry date is set.
     */
    public function isExpired(): bool
    {
        $validUntil = $this->getValidUntil();

        return $validUntil !== null && $validUntil < new DateTimeImmutable();
    }
}
