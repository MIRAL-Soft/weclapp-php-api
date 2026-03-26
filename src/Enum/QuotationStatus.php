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
    case InProcess   = 'QUOTATION_IN_PROCESS';
    case Sent        = 'QUOTATION_SENT';
    case Accepted    = 'QUOTATION_ACCEPTED';
    case Rejected    = 'QUOTATION_REJECTED';
    case Expired     = 'QUOTATION_EXPIRED';
    case Cancelled   = 'CANCELLED';
}
