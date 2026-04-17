<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Possible values for the invoicingType field on salesOrderItem.
 *
 * Controls how service items are billed.
 *
 * @see https://www.weclapp.com/api/openapi_v2.json → invoicingType schema
 */
enum InvoicingType: string
{
    /** Billed based on actual recorded effort (time tracking). */
    case Effort = 'EFFORT';

    /** Billed at a pre-agreed fixed price regardless of effort. */
    case FixedPrice = 'FIXED_PRICE';
}
