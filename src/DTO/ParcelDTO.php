<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents a single parcel within a Shipment in the weclapp API.
 *
 * Maps to the parcel schema. Instances are embedded inside
 * ShipmentDTO::$parcels. Each shipment can contain multiple parcels,
 * each with its own tracking information and physical dimensions.
 *
 * @see \miralsoft\weclapp\api\DTO\ShipmentDTO
 */
final class ParcelDTO extends AbstractDTO
{
    /**
     * @param string      $id                                     Internal weclapp UUID.
     * @param string      $version                                Record version (optimistic locking).
     * @param int         $createdDate                            Creation timestamp in epoch milliseconds.
     * @param int         $lastModifiedDate                       Last modification timestamp in epoch milliseconds.
     * @param string|null $declaredValueAmount                    Declared customs value as a decimal string.
     * @param string|null $declaredValueCurrencyId                Currency ID for the declared value.
     * @param bool        $dhlGoGreenPlusService                  DHL GoGreen Plus service enabled.
     * @param bool        $dhlPostalDeliveredDutyPaidService      DHL postal delivered duty paid service enabled.
     * @param bool        $dhlPremiumInternationalService         DHL premium international service enabled.
     * @param int         $height                                 Package height in mm.
     * @param int         $length                                 Package length in mm.
     * @param int         $positionNumber                         Position within the shipment's parcel list (1-based).
     * @param string|null $reference                              Customer reference number for this parcel.
     * @param bool        $saturdayDelivery                       Request Saturday delivery.
     * @param string|null $shippingCarrierAddition                Additional carrier service identifier.
     * @param string|null $shippingCarrierId                      ID of the shipping carrier for this parcel.
     * @param int         $shippingLabelsCount                    Number of shipping labels generated.
     * @param string|null $trackingId                             Carrier tracking ID / parcel number.
     * @param string|null $trackingUrl                            URL to track this parcel online.
     * @param bool        $useDeliveryDateAsPreferredDeliveryDate Use the shipment delivery date as preferred delivery date.
     * @param string|null $weight                                 Package weight as a decimal string (kg).
     * @param int         $width                                  Package width in mm.
     * @param list<CustomAttributeDTO> $customAttributes          Custom attribute values.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $declaredValueAmount,
        public readonly ?string $declaredValueCurrencyId,
        public readonly bool    $dhlGoGreenPlusService,
        public readonly bool    $dhlPostalDeliveredDutyPaidService,
        public readonly bool    $dhlPremiumInternationalService,
        public readonly int     $height,
        public readonly int     $length,
        public readonly int     $positionNumber,
        public readonly ?string $reference,
        public readonly bool    $saturdayDelivery,
        public readonly ?string $shippingCarrierAddition,
        public readonly ?string $shippingCarrierId,
        public readonly int     $shippingLabelsCount,
        public readonly ?string $trackingId,
        public readonly ?string $trackingUrl,
        public readonly bool    $useDeliveryDateAsPreferredDeliveryDate,
        public readonly ?string $weight,
        public readonly int     $width,
        public readonly array   $customAttributes,
    ) {}

    /**
     * Create a ParcelDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                                     self::str($data, 'id'),
            version:                                self::str($data, 'version'),
            createdDate:                            self::int($data, 'createdDate'),
            lastModifiedDate:                       self::int($data, 'lastModifiedDate'),
            declaredValueAmount:                    self::strOrNull($data, 'declaredValueAmount'),
            declaredValueCurrencyId:                self::strOrNull($data, 'declaredValueCurrencyId'),
            dhlGoGreenPlusService:                  self::bool($data, 'dhlGoGreenPlusService'),
            dhlPostalDeliveredDutyPaidService:      self::bool($data, 'dhlPostalDeliveredDutyPaidService'),
            dhlPremiumInternationalService:         self::bool($data, 'dhlPremiumInternationalService'),
            height:                                 self::int($data, 'height'),
            length:                                 self::int($data, 'length'),
            positionNumber:                         self::int($data, 'positionNumber'),
            reference:                              self::strOrNull($data, 'reference'),
            saturdayDelivery:                       self::bool($data, 'saturdayDelivery'),
            shippingCarrierAddition:                self::strOrNull($data, 'shippingCarrierAddition'),
            shippingCarrierId:                      self::strOrNull($data, 'shippingCarrierId'),
            shippingLabelsCount:                    self::int($data, 'shippingLabelsCount'),
            trackingId:                             self::strOrNull($data, 'trackingId'),
            trackingUrl:                            self::strOrNull($data, 'trackingUrl'),
            useDeliveryDateAsPreferredDeliveryDate: self::bool($data, 'useDeliveryDateAsPreferredDeliveryDate'),
            weight:                                 self::strOrNull($data, 'weight'),
            width:                                  self::int($data, 'width'),
            customAttributes:                       array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
        );
    }

    /**
     * Returns the parcel weight as a float (kg), or null if not set.
     */
    public function getWeight(): ?float
    {
        return $this->weight !== null ? (float) $this->weight : null;
    }

    /**
     * Returns the declared customs value as a float, or null if not set.
     */
    public function getDeclaredValueAmount(): ?float
    {
        return $this->declaredValueAmount !== null ? (float) $this->declaredValueAmount : null;
    }
}
