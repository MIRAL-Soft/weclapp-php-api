<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Possible values for the itemType field on salesOrderItem and salesInvoiceItem.
 *
 * @see https://www.weclapp.com/api/openapi_v2.json → itemType schema
 */
enum ItemType: string
{
    /** Standard article line item. */
    case Default = 'DEFAULT';

    /** Free-text position with no article reference. */
    case FreeText = 'FREE_TEXT';

    /** Service item billed by effort or fixed price. */
    case Service = 'SERVICE';

    /** Service item linked to a service quota. */
    case ServiceQuota = 'SERVICE_QUOTA';
}
