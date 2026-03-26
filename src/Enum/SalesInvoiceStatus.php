<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Known status values for weclapp Sales Invoices.
 *
 * @example
 * if (SalesInvoiceStatus::tryFrom($invoice->status) === SalesInvoiceStatus::Open) {
 *     $invoice->sendReminder();
 * }
 */
enum SalesInvoiceStatus: string
{
    case Draft    = 'DRAFT';
    case Open     = 'OPEN';
    case Paid     = 'PAID';
    case Overdue  = 'OVERDUE';
    case Cancelled = 'CANCELLED';
    case Credited = 'CREDITED';
}
