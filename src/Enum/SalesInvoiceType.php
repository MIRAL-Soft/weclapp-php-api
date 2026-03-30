<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Invoice type values for weclapp Sales Invoices.
 *
 * The type determines what kind of billing document a salesInvoice record
 * represents. Cancellation invoices are identified by CreditNote.
 *
 * To fetch only cancellation invoices via the API, filter by this type:
 *   GET /salesInvoice?salesInvoiceType-eq=CREDIT_NOTE
 * Or use SalesInvoiceResource::findCreditNotes().
 *
 * @example
 * if (SalesInvoiceType::tryFrom($invoice->salesInvoiceType) === SalesInvoiceType::CreditNote) {
 *     // This is a cancellation invoice — download with getPdf() as usual
 *     $pdf = $client->salesInvoices()->getPdf($invoice->id);
 * }
 */
enum SalesInvoiceType: string
{
    /** Advance payment invoice — partial payment before goods/services are delivered. */
    case AdvancePaymentInvoice = 'ADVANCE_PAYMENT_INVOICE';

    /**
     * Cancellation invoice / credit note (CLX-number range).
     *
     * Created when an existing invoice is cancelled. The original invoice
     * receives status CANCELLED and its cancellationNumber field is set to
     * the credit note's invoiceNumber (e.g. "CLX-1061").
     * The credit note itself carries precedingSalesInvoiceId pointing back
     * to the original invoice.
     */
    case CreditNote = 'CREDIT_NOTE';

    /** Final invoice settling all prior advance payments. */
    case FinalInvoice = 'FINAL_INVOICE';

    /** Partial payment invoice — covers a portion of the total amount. */
    case PartPaymentInvoice = 'PART_PAYMENT_INVOICE';

    /** Prepayment invoice — requests full payment before delivery. */
    case PrepaymentInvoice = 'PREPAYMENT_INVOICE';

    /** Retail / point-of-sale invoice. */
    case RetailInvoice = 'RETAIL_INVOICE';

    /** Standard sales invoice (RE-number range). */
    case StandardInvoice = 'STANDARD_INVOICE';
}
