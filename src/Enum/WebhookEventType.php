<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Known event type values for weclapp Webhooks.
 *
 * Use these constants when registering webhook subscriptions to avoid typos.
 *
 * @example
 * $client->webhooks()->register(
 *     eventType:   WebhookEventType::PartyUpdated->value,
 *     callbackUrl: 'https://my-app.example.com/weclapp-events',
 * );
 */
enum WebhookEventType: string
{
    // Party events (customers, contacts, suppliers)
    case PartyCreated = 'party.created';
    case PartyUpdated = 'party.updated';
    case PartyDeleted = 'party.deleted';

    // Article events
    case ArticleCreated = 'article.created';
    case ArticleUpdated = 'article.updated';
    case ArticleDeleted = 'article.deleted';

    // Sales Order events
    case SalesOrderCreated = 'salesOrder.created';
    case SalesOrderUpdated = 'salesOrder.updated';
    case SalesOrderDeleted = 'salesOrder.deleted';

    // Sales Invoice events
    case SalesInvoiceCreated = 'salesInvoice.created';
    case SalesInvoiceUpdated = 'salesInvoice.updated';

    // Quotation events
    case QuotationCreated = 'quotation.created';
    case QuotationUpdated = 'quotation.updated';
}
