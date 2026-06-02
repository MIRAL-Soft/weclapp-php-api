<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Entity types that can carry custom attributes in weclapp (`customAttributeEntityType`).
 *
 * Used as the entry in a custom attribute definition's `entities` array to scope
 * the definition to one or more entity types (e.g. `salesOrder`).
 *
 * **Important:** weclapp scopes a definition via the `entities` array — NOT via a
 * separate `attributeEntityType` field. Passing `attributeEntityType` on create
 * triggers a validation error.
 *
 * @see \miralsoft\weclapp\api\Resource\CustomAttributeDefinitionResource
 */
enum CustomAttributeEntityType: string
{
    case Article              = 'article';
    case BlanketPurchaseOrder = 'blanketPurchaseOrder';
    case BlanketSalesOrder    = 'blanketSalesOrder';
    case Campaign             = 'campaign';
    case Contract             = 'contract';
    case Customer             = 'customer';
    case IncomingGoods        = 'incomingGoods';
    case Opportunity          = 'opportunity';
    case Party                = 'party';
    case PerformanceRecord    = 'performanceRecord';
    case ProductionOrder      = 'productionOrder';
    case Project              = 'project';
    case PurchaseInvoice      = 'purchaseInvoice';
    case PurchaseOrder        = 'purchaseOrder';
    case PurchaseOrderRequest = 'purchaseOrderRequest';
    case Quotation            = 'quotation';
    case RecurringInvoice     = 'recurringInvoice';
    case SalesInvoice         = 'salesInvoice';
    case SalesOrder           = 'salesOrder';
    case SerialNumber         = 'serialNumber';
    case Shipment             = 'shipment';
    case Supplier             = 'supplier';
    case Ticket               = 'ticket';
    case User                 = 'user';
}
