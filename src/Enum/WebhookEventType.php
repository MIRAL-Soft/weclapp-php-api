<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * @deprecated Use WebhookEntityName combined with WebhookResource::register() boolean flags instead.
 *
 * This enum used combined "entity.action" strings (e.g. "party.created") which do not exist
 * in the weclapp API. The API uses a separate entityName field plus atCreate / atUpdate / atDelete
 * boolean flags on the webhook record.
 *
 * Migration guide:
 *
 *   Before (wrong):
 *     $client->webhooks()->register(
 *         eventType:   WebhookEventType::PartyUpdated->value,
 *         callbackUrl: 'https://my-app.example.com/webhooks/weclapp',
 *     );
 *
 *   After (correct):
 *     $client->webhooks()->register(
 *         entityName: WebhookEntityName::Party->value,
 *         url:        'https://my-app.example.com/webhooks/weclapp',
 *         atUpdate:   true,
 *     );
 *
 * @see WebhookEntityName
 * @see \miralsoft\weclapp\api\Resource\WebhookResource::register()
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
