<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Billing/repeat interval period of a weclapp recurring invoice (`intervalType`).
 *
 * Combined with the numeric `interval` (e.g. interval=1 + MONTHLY = "every month",
 * interval=3 + MONTHLY = "every quarter"), this expresses how often the recurring
 * invoice is generated.
 *
 * **Confirmed live against the miralsoft tenant:** `MONTHLY`, `YEARLY`.
 * The remaining cases reflect weclapp's documented interval set and are mapped
 * defensively — {@see \miralsoft\weclapp\api\DTO\RecurringInvoiceDTO::getIntervalType()}
 * uses `tryFrom()` and returns null for any value not listed here, while the raw
 * `intervalType` string is always available on the DTO.
 *
 * @see \miralsoft\weclapp\api\DTO\RecurringInvoiceDTO
 */
enum RecurringInvoiceIntervalType: string
{
    case Daily      = 'DAILY';
    case Weekly     = 'WEEKLY';
    case Monthly    = 'MONTHLY';
    case Quarterly  = 'QUARTERLY';
    case HalfYearly = 'HALF_YEARLY';
    case Yearly     = 'YEARLY';
}
