<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Action values in incoming weclapp webhook payloads (the `type` field).
 *
 * Live-confirmed payload structure (logged delivery, 2026-06-11):
 *   {"entityId":"975300","entityName":"contact","type":"UPDATE"}
 *
 * `UPDATE` is live-confirmed; `CREATE` and `DELETE` follow from the
 * atCreate/atUpdate/atDelete subscription flags on the webhook record.
 *
 * @see \miralsoft\weclapp\api\DTO\WebhookEventDTO
 */
enum WebhookEventAction: string
{
    case Create = 'CREATE';
    case Update = 'UPDATE';
    case Delete = 'DELETE';
}
