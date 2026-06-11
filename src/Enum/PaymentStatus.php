<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Payment status values for weclapp Sales Invoices (`paymentStatus`).
 *
 * All 6 values from the weclapp OpenAPI `paymentStatus` schema.
 *
 * @example
 * $open = $client->salesInvoices()->listAll(
 *     QueryBuilder::new()->filterEq('paymentStatus', PaymentStatus::Open->value)
 * );
 */
enum PaymentStatus: string
{
    case ClearedWithCreditNote = 'CLEARED_WITH_CREDIT_NOTE';
    case CreditNoteCleared     = 'CREDIT_NOTE_CLEARED';
    case NoOpenItem            = 'NO_OPEN_ITEM';
    case Open                  = 'OPEN';
    case Paid                  = 'PAID';
    case Unknown               = 'UNKNOWN';
}
