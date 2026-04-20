<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents e-commerce order metadata linked to a Sales Order in the weclapp API.
 *
 * Maps to the ecommerceOrder schema. Embedded inside SalesOrderDTO::$ecommerceOrder.
 * All 6 fields of the weclapp OpenAPI ecommerceOrder schema are covered.
 *
 * Note: this schema has no identity fields (no id/version/timestamps).
 *
 * @see \miralsoft\weclapp\api\DTO\SalesOrderDTO
 */
final class EcommerceOrderDTO extends AbstractDTO
{
    /**
     * @param string|null  $amazonFeedSubmissionId       Amazon feed submission ID (readOnly).
     * @param string|null  $amazonInvoiceUploadSuccess   Amazon invoice upload success status (readOnly, enum: amazonInvoiceUploadSuccess).
     * @param string|null  $amazonSalesChannel           Amazon sales channel identifier (enum: amazonSalesChannel).
     * @param bool         $easyShipped                  Whether Amazon Easy Ship is used for this order.
     * @param string|null  $ecommerceId                  External e-commerce platform order ID.
     * @param string|null  $externalConnectionId         ID of the external connection / integration.
     */
    public function __construct(
        public readonly ?string $amazonFeedSubmissionId,
        public readonly ?string $amazonInvoiceUploadSuccess,
        public readonly ?string $amazonSalesChannel,
        public readonly bool    $easyShipped,
        public readonly ?string $ecommerceId,
        public readonly ?string $externalConnectionId,
    ) {}

    /**
     * Create an EcommerceOrderDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            amazonFeedSubmissionId:     self::strOrNull($data, 'amazonFeedSubmissionId'),
            amazonInvoiceUploadSuccess: self::strOrNull($data, 'amazonInvoiceUploadSuccess'),
            amazonSalesChannel:         self::strOrNull($data, 'amazonSalesChannel'),
            easyShipped:                self::bool($data, 'easyShipped'),
            ecommerceId:                self::strOrNull($data, 'ecommerceId'),
            externalConnectionId:       self::strOrNull($data, 'externalConnectionId'),
        );
    }
}
