<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Document type values for the weclapp /api/v2/document endpoint.
 *
 * For invoice-related document processing the two most relevant types are:
 *   - SalesInvoice             → regular invoice PDF (RE-number range)
 *   - SalesInvoiceCancellation → cancellation invoice PDF (CLX-number range)
 *
 * @example
 * $docs = $client->documents()->findByEntity($invoiceId, 'salesInvoice');
 * foreach ($docs as $doc) {
 *     if (DocumentType::tryFrom($doc->documentType) === DocumentType::SalesInvoiceCancellation) {
 *         $pdf = $client->documents()->download($doc->id);
 *     }
 * }
 */
enum DocumentType: string
{
    case ArticleDatasheet                        = 'ARTICLE_DATASHEET';
    case ArticleLabel                            = 'ARTICLE_LABEL';
    case BlanketPurchaseOrder                    = 'BLANKET_PURCHASE_ORDER';
    case BlanketSalesOrder                       = 'BLANKET_SALES_ORDER';
    case CancellationUbl                         = 'CANCELLATION_UBL';
    case CancellationXr                          = 'CANCELLATION_XR';
    case Contract                                = 'CONTRACT';
    case CreditAdvice                            = 'CREDIT_ADVICE';
    case CreditAdviceCancellation                = 'CREDIT_ADVICE_CANCELLATION';
    case CreditAdvicePreliminaryInvoice          = 'CREDIT_ADVICE_PRELIMINARY_INVOICE';
    case CreditAdviceUbl                         = 'CREDIT_ADVICE_UBL';
    case CreditAdviceXr                          = 'CREDIT_ADVICE_XR';
    case CrmEventLetter                          = 'CRM_EVENT_LETTER';
    case CustomerArticlePriceList                = 'CUSTOMER_ARTICLE_PRICE_LIST';
    case Dunning                                 = 'DUNNING';
    case IncomingGoods                           = 'INCOMING_GOODS';
    case IncomingGoodsFromReturn                 = 'INCOMING_GOODS_FROM_RETURN';
    case IncomingGoodsReturnsPickupNote          = 'INCOMING_GOODS_RETURNS_PICKUP_NOTE';
    case InventoryTaking                         = 'INVENTORY_TAKING';
    case PerformanceRecord                       = 'PERFORMANCE_RECORD';
    case ProductionOrder                         = 'PRODUCTION_ORDER';
    case PurchaseInvoice                         = 'PURCHASE_INVOICE';
    case PurchaseInvoiceFatturapa                = 'PURCHASE_INVOICE_FATTURAPA';
    case PurchaseInvoiceZugferd                  = 'PURCHASE_INVOICE_ZUGFERD';
    case PurchaseOrder                           = 'PURCHASE_ORDER';
    case PurchaseOrderCancellation               = 'PURCHASE_ORDER_CANCELLATION';
    case PurchaseOrderDefault                    = 'PURCHASE_ORDER_DEFAULT';
    case PurchaseOrderRequest                    = 'PURCHASE_ORDER_REQUEST';
    case PurchaseOrderRequestOfferItemCsv        = 'PURCHASE_ORDER_REQUEST_OFFER_ITEM_CSV';
    case PurchaseOrderRequestSupplierDocument    = 'PURCHASE_ORDER_REQUEST_SUPPLIER_DOCUMENT';
    case Quotation                               = 'QUOTATION';
    case QuotationDefault                        = 'QUOTATION_DEFAULT';

    /** Regular sales invoice PDF (RE-number range). */
    case SalesInvoice                            = 'SALES_INVOICE';

    /** Cancellation invoice PDF (CLX-number range). Use DocumentDTO::isCancellationInvoice() to check. */
    case SalesInvoiceCancellation                = 'SALES_INVOICE_CANCELLATION';

    case SalesInvoiceDefault                     = 'SALES_INVOICE_DEFAULT';
    case SalesInvoiceFatturapa                   = 'SALES_INVOICE_FATTURAPA';
    case SalesInvoicePreliminary                 = 'SALES_INVOICE_PRELIMINARY';
    case SalesInvoiceQr                          = 'SALES_INVOICE_QR';
    case SalesInvoiceUbl                         = 'SALES_INVOICE_UBL';
    case SalesInvoiceXr                          = 'SALES_INVOICE_XR';
    case SalesOrder                              = 'SALES_ORDER';
    case SalesOrderDefault                       = 'SALES_ORDER_DEFAULT';
    case ShipmentCustomsDeclaration              = 'SHIPMENT_CUSTOMS_DECLARATION';
    case ShipmentDeliveryLabel                   = 'SHIPMENT_DELIVERY_LABEL';
    case ShipmentDeliveryNote                    = 'SHIPMENT_DELIVERY_NOTE';
    case ShipmentDeliveryNoteDefault             = 'SHIPMENT_DELIVERY_NOTE_DEFAULT';
    case ShipmentPickingList                     = 'SHIPMENT_PICKING_LIST';
    case ShipmentProformaInvoice                 = 'SHIPMENT_PROFORMA_INVOICE';
    case ShipmentReturnDeliveryNote              = 'SHIPMENT_RETURN_DELIVERY_NOTE';
    case ShipmentReturnLabel                     = 'SHIPMENT_RETURN_LABEL';
    case ShipmentSerialNumbersCsv                = 'SHIPMENT_SERIAL_NUMBERS_CSV';
    case Ticket                                  = 'TICKET';
    case ZugferdValidation                       = 'ZUGFERD_VALIDATION';
}
