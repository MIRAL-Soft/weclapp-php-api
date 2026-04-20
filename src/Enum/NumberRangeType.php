<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * All entity types that have a configurable number range in weclapp.
 *
 * Each case corresponds to a document series whose prefix and increment
 * are configured under Settings → Number Ranges in the weclapp UI.
 * The API exposes these via the /api/v2/numberRange and
 * /api/v2/numberRangeValue endpoints.
 *
 * The most relevant case for DATEV-style integrations is ProformaInvoice:
 * proforma invoices are not stored with a dedicated salesInvoiceType value —
 * they are identified solely by their invoiceNumber prefix (e.g. "PR-").
 * Use NumberRangeResource::getProformaInvoicePrefix() to retrieve the
 * tenant-specific configured prefix at runtime.
 *
 * @see \miralsoft\weclapp\api\Resource\NumberRangeResource
 * @see \miralsoft\weclapp\api\Resource\NumberRangeValueResource
 */
enum NumberRangeType: string
{
    case AccountingExportLog       = 'ACCOUNTING_EXPORT_LOG';
    case AccountingTransaction     = 'ACCOUNTING_TRANSACTION';
    case Article                   = 'ARTICLE';
    case BillingReference          = 'BILLING_REFERENCE';
    case BlanketPurchaseOrder      = 'BLANKET_PURCHASE_ORDER';
    case BlanketSalesOrder         = 'BLANKET_SALES_ORDER';
    case Campaign                  = 'CAMPAIGN';
    case Contract                  = 'CONTRACT';
    case CostCenter                = 'COST_CENTER';
    case CreditAdvice              = 'CREDIT_ADVICE';
    case CreditAdviceCancellation  = 'CREDIT_ADVICE_CANCELLATION';
    case CreditAdvicePreliminary   = 'CREDIT_ADVICE_PRELIMINARY';
    case Employee                  = 'EMPLOYEE';
    case IncomingGoods             = 'INCOMING_GOODS';
    case InternalTransportReference = 'INTERNAL_TRANSPORT_REFERENCE';
    case Inventory                 = 'INVENTORY';
    case InventoryItem             = 'INVENTORY_ITEM';
    case LedgerAccountCreditor     = 'LEDGER_ACCOUNT_CREDITOR';
    case LedgerAccountDebtor       = 'LEDGER_ACCOUNT_DEBTOR';
    case LedgerAccountImpersonal   = 'LEDGER_ACCOUNT_IMPERSONAL';
    case Opportunity               = 'OPPORTUNITY';
    case PartyCustomer             = 'PARTY_CUSTOMER';
    case PartySupplier             = 'PARTY_SUPPLIER';
    case PerformanceRecord         = 'PERFORMANCE_RECORD';
    case PmProject                 = 'PM_PROJECT';
    case ProductionOrder           = 'PRODUCTION_ORDER';

    /**
     * Proforma invoice number range.
     *
     * Proforma invoices have no dedicated salesInvoiceType value in the API.
     * They are distinguished exclusively by their invoiceNumber prefix,
     * which is configured here (default: "PR-").
     * They must NOT be forwarded to accounting systems like DATEV.
     */
    case ProformaInvoice           = 'PROFORMA_INVOICE';

    case PurchaseInvoice           = 'PURCHASE_INVOICE';
    case PurchaseOpenItem          = 'PURCHASE_OPEN_ITEM';
    case PurchaseOrder             = 'PURCHASE_ORDER';
    case PurchaseOrderRequest      = 'PURCHASE_ORDER_REQUEST';
    case PurchaseRequisition       = 'PURCHASE_REQUISITION';
    case Quotation                 = 'QUOTATION';
    case RecurringInvoice          = 'RECURRING_INVOICE';
    case SalesInvoice              = 'SALES_INVOICE';
    case SalesInvoiceCancellation  = 'SALES_INVOICE_CANCELLATION';
    case SalesInvoiceFinalStandard = 'SALES_INVOICE_FINAL_STANDARD';
    case SalesInvoicePreliminary   = 'SALES_INVOICE_PRELIMINARY';
    case SalesOpenItem             = 'SALES_OPEN_ITEM';
    case SalesOrder                = 'SALES_ORDER';
    case SerialNumber              = 'SERIAL_NUMBER';
    case ServiceQuota              = 'SERVICE_QUOTA';
    case Shipment                  = 'SHIPMENT';
    case Ticket                    = 'TICKET';
    case TransportationOrder       = 'TRANSPORTATION_ORDER';
    case WarehouseStockMovement    = 'WAREHOUSE_STOCK_MOVEMENT';
}
