<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;
use miralsoft\weclapp\api\Enum\RecurringInvoiceIntervalType;

/**
 * Represents a weclapp Recurring Invoice (wiederkehrende Rechnung).
 *
 * A recurring invoice is a template that weclapp uses to automatically generate
 * sales invoices at a fixed interval (e.g. monthly, yearly). It is the
 * authoritative source for the billing/repeat interval of a managed-service
 * contract and the billed positions/quantities/amounts per customer.
 *
 * Maps to the `/api/v2/recurringInvoice` endpoint.
 *
 * **Read-only:** the weclapp API exposes only GET/HEAD on this endpoint
 * (POST/PUT/DELETE return HTTP 405). There is therefore no create/update/delete.
 *
 * **Interval model:** the repeat cadence is expressed by the numeric `interval`
 * combined with the `intervalType` period (e.g. interval=1 + MONTHLY = monthly,
 * interval=3 + MONTHLY = quarterly). `nextInvoiceDate` holds the next scheduled
 * run. There is no explicit "active" flag in the schema — a future
 * `nextInvoiceDate` is the practical signal that generation is ongoing.
 *
 * @see \miralsoft\weclapp\api\Resource\RecurringInvoiceResource
 * @see \miralsoft\weclapp\api\DTO\RecurringInvoiceItemDTO
 * @see \miralsoft\weclapp\api\Enum\RecurringInvoiceIntervalType
 */
final class RecurringInvoiceDTO extends AbstractDTO
{
    /**
     * @param string       $id                     Internal weclapp UUID (readOnly).
     * @param string       $version                Optimistic locking version string (readOnly).
     * @param int          $createdDate            Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate       Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $recurringInvoiceNumber Human-readable number (e.g. "1001").
     * @param string       $customerId             ID of the customer this recurring invoice bills.
     * @param int|null     $interval               Numeric repeat factor (e.g. 1, 3) combined with intervalType.
     * @param string|null  $intervalType           Repeat period. See RecurringInvoiceIntervalType (e.g. MONTHLY, YEARLY).
     * @param int|null     $intervalDayOfMonth     Day of month on which the invoice is generated.
     * @param int|null     $nextInvoiceDate        Next scheduled generation date in epoch milliseconds.
     * @param string|null  $desiredInvoiceStatus   Target status of the generated invoices (e.g. OPEN_ITEM_CREATED).
     * @param string|null  $servicePeriodFromKey   Rule key for the service-period start of generated invoices.
     * @param string|null  $servicePeriodToKey     Rule key for the service-period end of generated invoices.
     * @param string|null  $netAmount              Total net amount per generated invoice (decimal string, readOnly).
     * @param string|null  $grossAmount            Total gross amount per generated invoice (decimal string, readOnly).
     * @param string|null  $responsibleUserId      ID of the responsible user.
     * @param string|null  $mailTemplateId         ID of the mail template used when sending generated invoices.
     * @param string|null  $mailAccountId          ID of the mail account used when sending generated invoices.
     * @param bool         $sentToRecipient        Whether generated invoices are flagged as sent.
     * @param list<RecurringInvoiceItemDTO>  $recurringInvoiceItems Billed line items.
     * @param list<CustomAttributeDTO>       $customAttributes      Custom attribute values.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,

        public readonly ?string $recurringInvoiceNumber,
        public readonly string  $customerId,

        public readonly ?int    $interval,
        public readonly ?string $intervalType,
        public readonly ?int    $intervalDayOfMonth,
        public readonly ?int    $nextInvoiceDate,
        public readonly ?string $desiredInvoiceStatus,
        public readonly ?string $servicePeriodFromKey,
        public readonly ?string $servicePeriodToKey,

        public readonly ?string $netAmount,
        public readonly ?string $grossAmount,

        public readonly ?string $responsibleUserId,
        public readonly ?string $mailTemplateId,
        public readonly ?string $mailAccountId,
        public readonly bool    $sentToRecipient,

        public readonly array   $recurringInvoiceItems,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a RecurringInvoiceDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                     self::str($data, 'id'),
            version:                self::str($data, 'version'),
            createdDate:            self::int($data, 'createdDate'),
            lastModifiedDate:       self::int($data, 'lastModifiedDate'),

            recurringInvoiceNumber: self::strOrNull($data, 'recurringInvoiceNumber'),
            customerId:             self::str($data, 'customerId'),

            interval:               self::intOrNull($data, 'interval'),
            intervalType:           self::strOrNull($data, 'intervalType'),
            intervalDayOfMonth:     self::intOrNull($data, 'intervalDayOfMonth'),
            nextInvoiceDate:        self::intOrNull($data, 'nextInvoiceDate'),
            desiredInvoiceStatus:   self::strOrNull($data, 'desiredInvoiceStatus'),
            servicePeriodFromKey:   self::strOrNull($data, 'servicePeriodFromKey'),
            servicePeriodToKey:     self::strOrNull($data, 'servicePeriodToKey'),

            netAmount:              self::strOrNull($data, 'netAmount'),
            grossAmount:            self::strOrNull($data, 'grossAmount'),

            responsibleUserId:      self::strOrNull($data, 'responsibleUserId'),
            mailTemplateId:         self::strOrNull($data, 'mailTemplateId'),
            mailAccountId:          self::strOrNull($data, 'mailAccountId'),
            sentToRecipient:        self::bool($data, 'sentToRecipient'),

            recurringInvoiceItems:  array_map(
                static fn (array $item) => RecurringInvoiceItemDTO::fromArray($item),
                self::arr($data, 'recurringInvoiceItems'),
            ),
            customAttributes:       array_map(
                static fn (array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
        );
    }

    /**
     * Returns the repeat period as a typed enum, or null for unmapped values.
     *
     * The raw value is always available via the `$intervalType` property.
     */
    public function getIntervalType(): ?RecurringInvoiceIntervalType
    {
        return $this->intervalType !== null
            ? RecurringInvoiceIntervalType::tryFrom($this->intervalType)
            : null;
    }

    /**
     * Returns a human-readable cadence string, e.g. "every 1 MONTHLY", "every 3 MONTHLY".
     *
     * Returns null if interval or intervalType is missing. Intended for logging /
     * display; for logic, use getIntervalType() + $interval directly.
     */
    public function getCadenceLabel(): ?string
    {
        if ($this->interval === null || $this->intervalType === null) {
            return null;
        }

        return sprintf('every %d %s', $this->interval, $this->intervalType);
    }

    /**
     * Returns the next scheduled generation date as a DateTimeImmutable, or null.
     */
    public function getNextInvoiceDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['nextInvoiceDate' => $this->nextInvoiceDate], 'nextInvoiceDate');
    }

    /** Returns the total net amount per generated invoice as a float, or null. */
    public function getNetAmount(): ?float
    {
        return $this->netAmount !== null ? (float) $this->netAmount : null;
    }

    /** Returns the total gross amount per generated invoice as a float, or null. */
    public function getGrossAmount(): ?float
    {
        return $this->grossAmount !== null ? (float) $this->grossAmount : null;
    }

    /** Returns the creation date as a DateTimeImmutable object. */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['createdDate' => $this->createdDate], 'createdDate');
    }

    /** Returns the last modification date as a DateTimeImmutable object. */
    public function getLastModifiedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['lastModifiedDate' => $this->lastModifiedDate], 'lastModifiedDate');
    }
}
