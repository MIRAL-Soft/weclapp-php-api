<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Status values for weclapp Sales Invoices, as returned by the API.
 *
 * Lifecycle order (typical flow):
 *   New → DocumentCreated → OpenItemCreated → EntryCompleted
 *
 * A cancelled invoice has status Cancelled. This status is set on the
 * original invoice when a credit note (CREDIT_NOTE) is created for it.
 *
 * @see SalesInvoiceType for the invoice type (e.g. CREDIT_NOTE vs STANDARD_INVOICE)
 *
 * @example
 * if (SalesInvoiceStatus::tryFrom($invoice->status) === SalesInvoiceStatus::OpenItemCreated) {
 *     // Invoice has been posted to accounting — payment is expected
 * }
 */
enum SalesInvoiceStatus: string
{
    /** Invoice has been created but not yet processed. */
    case New = 'NEW';

    /** Invoice document has been generated. */
    case DocumentCreated = 'DOCUMENT_CREATED';

    /** Invoice has been transferred to the open-item list (accounts receivable). */
    case OpenItemCreated = 'OPEN_ITEM_CREATED';

    /** Invoice has been fully completed and booked. */
    case EntryCompleted = 'ENTRY_COMPLETED';

    /** Invoice has been cancelled; a credit note (CREDIT_NOTE) was typically created. */
    case Cancelled = 'CANCELLED';
}
