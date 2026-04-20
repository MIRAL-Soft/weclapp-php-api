<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Known entity name values for weclapp Webhook subscriptions.
 *
 * The weclapp webhook API uses an entityName string (not a combined "entity.action" string)
 * together with the boolean flags atCreate, atUpdate, atDelete to define which events fire.
 *
 * Use these constants as the $entityName argument when registering webhook subscriptions.
 *
 * @example
 * use miralsoft\weclapp\api\Enum\WebhookEntityName;
 *
 * // Fire on any party change (create, update, or delete)
 * $client->webhooks()->register(
 *     entityName: WebhookEntityName::Party->value,
 *     url:        'https://my-app.example.com/webhooks/weclapp',
 *     atCreate:   true,
 *     atUpdate:   true,
 *     atDelete:   true,
 * );
 *
 * // Fire only when a sales order is created
 * $client->webhooks()->register(
 *     entityName: WebhookEntityName::SalesOrder->value,
 *     url:        'https://my-app.example.com/webhooks/weclapp',
 *     atCreate:   true,
 * );
 */
enum WebhookEntityName: string
{
    // Party (covers customers, contacts, suppliers)
    case Party = 'party';

    // Articles
    case Article = 'article';

    // Sales documents
    case SalesOrder   = 'salesOrder';
    case SalesInvoice = 'salesInvoice';
    case Quotation    = 'quotation';

    // Purchasing
    case PurchaseOrder   = 'purchaseOrder';
    case PurchaseInvoice = 'purchaseInvoice';

    // Shipments / Contracts / Tickets
    case Shipment = 'shipment';
    case Contract = 'contract';
    case Ticket   = 'ticket';
}
