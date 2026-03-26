<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Sales Order from the weclapp API.
 *
 * Sales orders map to the /api/v2/salesOrder endpoint.
 * PDF downloads are available via SalesOrderResource::getPdf().
 *
 * @see \miralsoft\weclapp\api\Resource\SalesOrderResource
 */
final class SalesOrderDTO extends AbstractDTO
{
    /**
     * @param string       $id                    Internal weclapp UUID.
     * @param string       $version               Optimistic locking version string.
     * @param int          $createdDate           Creation timestamp in epoch milliseconds.
     * @param int          $lastModifiedDate      Last modification timestamp in epoch milliseconds.
     * @param string       $orderNumber           Human-readable order number (e.g. "SO-10042").
     * @param string       $status                Order status (e.g. "ORDER_ENTRY_IN_PROGRESS", "ORDER_CONFIRMED").
     * @param string       $customerId            ID of the linked customer.
     * @param string|null  $customerNumber        Customer number for reference.
     * @param string|null  $customerName          Customer display name (denormalised).
     * @param string|null  $customerOrderNumber   Customer's own order reference number.
     * @param int          $orderDate             Order date in epoch milliseconds.
     * @param int|null     $deliveryDate          Requested delivery date in epoch milliseconds.
     * @param int|null     $shippingDate          Actual shipping date in epoch milliseconds.
     * @param string|null  $description           Internal description / comment.
     * @param float|null   $netAmount             Net order amount.
     * @param float|null   $grossAmount           Gross order amount (including tax).
     * @param string|null  $currency              Currency code (e.g. "EUR").
     * @param string|null  $salesChannel          Assigned sales channel.
     * @param string|null  $responsibleUserId     ID of the responsible weclapp user.
     * @param list<array>  $orderItems            Line items of this order.
     * @param list<array>  $tags                  List of tag objects.
     * @param list<array>  $customAttributes      List of custom attribute objects.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly string  $orderNumber,
        public readonly string  $status,
        public readonly string  $customerId,
        public readonly ?string $customerNumber,
        public readonly ?string $customerName,
        public readonly ?string $customerOrderNumber,
        public readonly int     $orderDate,
        public readonly ?int    $deliveryDate,
        public readonly ?int    $shippingDate,
        public readonly ?string $description,
        public readonly ?float  $netAmount,
        public readonly ?float  $grossAmount,
        public readonly ?string $currency,
        public readonly ?string $salesChannel,
        public readonly ?string $responsibleUserId,
        public readonly array   $orderItems,
        public readonly array   $tags,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a SalesOrderDTO from a raw weclapp API response array.
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
            orderNumber:          self::str($data, 'orderNumber'),
            status:               self::str($data, 'status'),
            customerId:           self::str($data, 'customerId'),
            customerNumber:       self::strOrNull($data, 'customerNumber'),
            customerName:         self::strOrNull($data, 'customerName'),
            customerOrderNumber:  self::strOrNull($data, 'customerOrderNumber'),
            orderDate:            self::int($data, 'orderDate'),
            deliveryDate:         self::intOrNull($data, 'deliveryDate'),
            shippingDate:         self::intOrNull($data, 'shippingDate'),
            description:          self::strOrNull($data, 'description'),
            netAmount:            self::floatOrNull($data, 'netAmount'),
            grossAmount:          self::floatOrNull($data, 'grossAmount'),
            currency:             self::strOrNull($data, 'currency'),
            salesChannel:         self::strOrNull($data, 'salesChannel'),
            responsibleUserId:    self::strOrNull($data, 'responsibleUserId'),
            orderItems:           self::arr($data, 'orderItems'),
            tags:                 self::arr($data, 'tags'),
            customAttributes:     self::arr($data, 'customAttributes'),
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
     * Returns the order date as a DateTimeImmutable object.
     */
    public function getOrderDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['orderDate' => $this->orderDate], 'orderDate');
    }

    /**
     * Returns the requested delivery date as a DateTimeImmutable object.
     */
    public function getDeliveryDate(): ?DateTimeImmutable
    {
        if ($this->deliveryDate === null) {
            return null;
        }

        return self::dateFromEpochMs(['deliveryDate' => $this->deliveryDate], 'deliveryDate');
    }
}
