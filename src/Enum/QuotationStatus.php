<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Known status values for weclapp Quotations (Angebote).
 *
 * @example
 * if (QuotationStatus::tryFrom($quotation->status) === QuotationStatus::Accepted) {
 *     $client->quotations()->convertToSalesOrder($quotation->id);
 * }
 */
enum QuotationStatus: string
{
    case InProcess          = 'QUOTATION_IN_PROCESS';
    case Sent               = 'QUOTATION_SENT';
    case Accepted           = 'QUOTATION_ACCEPTED';
    case Rejected           = 'QUOTATION_REJECTED';
    case Expired            = 'QUOTATION_EXPIRED';
    case Cancelled          = 'CANCELLED';

    /**
     * Short-form aliases returned by some weclapp tenants without the QUOTATION_ prefix.
     * These are equivalent to their full-form counterparts above.
     */
    case AcceptedShort      = 'ACCEPTED';
    case RejectedShort      = 'REJECTED';
    case InProcessShort     = 'IN_PROCESS';
    case SentShort          = 'SENT';
    case ExpiredShort       = 'EXPIRED';
}
