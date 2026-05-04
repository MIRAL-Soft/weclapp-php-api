<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * All known entityName values for weclapp Webhook subscriptions.
 *
 * Verified against a live weclapp tenant's webhook configuration UI on 2026-05-04.
 * The list covers 117 confirmed values. Some values may not be available in all
 * tenants depending on the licensed modules.
 *
 * Key findings confirmed on 2026-05-04:
 *   - `customer`, `contact` and `party` are DISTINCT entityNames — all three exist in parallel.
 *     Use the most specific one for your integration. `party` catches all party-type records.
 *   - There is NO `salesOrderItem` entityName. Changes to order line items are delivered
 *     as `salesOrder` update events containing the full updated order state with all items.
 *   - `webhook` is itself a valid entityName (meta-subscriptions that fire when a
 *     webhook subscription record changes).
 *
 * Cases marked @beta are present in the weclapp UI but may not be stable across all
 * tenants or weclapp versions. Use with caution in production integrations.
 *
 * Values retained from previous library versions that are NOT in the verified 117-entry
 * list (purchaseOrder, shipment, contract, ticket) may be valid in tenants with the
 * corresponding modules enabled — they are preserved but annotated accordingly.
 *
 * @see \miralsoft\weclapp\api\Resource\WebhookResource
 */
enum WebhookEntityName: string
{
    // -------------------------------------------------------------------------
    // Party / CRM
    // -------------------------------------------------------------------------

    /** All party records regardless of type (customers, contacts, suppliers, leads). */
    case Party = 'party';

    /** Customer-specific party records. See also {@see Party} for all party types. */
    case Customer = 'customer';

    /** Contact persons linked to a parent organisation. */
    case Contact = 'contact';

    /** Supplier records. */
    case Supplier = 'supplier';

    case Lead               = 'lead';
    case Opportunity        = 'opportunity';
    case Campaign           = 'campaign';
    case CampaignParticipant = 'campaignParticipant';
    case CrmEvent           = 'crmEvent';
    case CrmEventCategory   = 'crmEventCategory';
    case CrmCallCategory    = 'crmCallCategory';
    case PartyRating        = 'partyRating';
    case LeadRating         = 'leadRating';
    case LeadSource         = 'leadSource';
    case CustomerCategory   = 'customerCategory';
    case CustomerLeadLossReason = 'customerLeadLossReason';
    case CustomerTopic      = 'customerTopic';
    case OpportunityTopic   = 'opportunityTopic';
    case OpportunityWinLossReason = 'opportunityWinLossReason';

    /** @beta */
    case SalesPipeline      = 'salesPipeline';

    case SalesStage         = 'salesStage';
    case SalesTeam          = 'salesTeam';
    case CompanySize        = 'companySize';
    case LegalForm          = 'legalForm';
    case Sector             = 'sector';

    // -------------------------------------------------------------------------
    // Articles / Products
    // -------------------------------------------------------------------------

    case Article                      = 'article';
    case ArticleCategory              = 'articleCategory';
    case ArticleCategoryClassification = 'articleCategoryClassification';
    case ArticleItemGroup             = 'articleItemGroup';
    case ArticlePrice                 = 'articlePrice';
    case ArticleRating                = 'articleRating';
    case ArticleStatus                = 'articleStatus';
    case ArticleSupplySource          = 'articleSupplySource';
    case ArticleAccountingCode        = 'articleAccountingCode';
    case Manufacturer                 = 'manufacturer';

    // -------------------------------------------------------------------------
    // Sales documents
    // -------------------------------------------------------------------------

    case SalesOrder       = 'salesOrder';
    case SalesInvoice     = 'salesInvoice';
    case SalesOpenItem    = 'salesOpenItem';
    case Quotation        = 'quotation';
    case BlanketSalesOrder = 'blanketSalesOrder';

    /** @beta */
    case RecurringInvoice = 'recurringInvoice';

    // -------------------------------------------------------------------------
    // Purchasing
    // -------------------------------------------------------------------------

    case PurchaseInvoice = 'purchaseInvoice';
    case PurchaseOpenItem = 'purchaseOpenItem';

    /**
     * Not in the verified 117-entry list; may be module-specific or not available in
     * all tenants. Retained from a previous library version.
     */
    case PurchaseOrder = 'purchaseOrder';

    // -------------------------------------------------------------------------
    // Warehouse / Logistics
    // -------------------------------------------------------------------------

    case ShipmentMethod             = 'shipmentMethod';
    case ShippingCarrier            = 'shippingCarrier';
    case InternalTransportReference = 'internalTransportReference';
    case LoadingEquipmentIdentifier = 'loadingEquipmentIdentifier';
    case FulfillmentProvider        = 'fulfillmentProvider';

    /**
     * Shipment / delivery note document. Not in the verified 117-entry list; may be
     * module-specific. Retained from a previous library version.
     */
    case Shipment = 'shipment';

    // -------------------------------------------------------------------------
    // Finance / Accounting
    // -------------------------------------------------------------------------

    case AccountingTransaction  = 'accountingTransaction';

    /** @beta */
    case AccountCategory        = 'accountCategory';

    case BankAccount            = 'bankAccount';
    case CashAccount            = 'cashAccount';
    case CashAccountSheet       = 'cashAccountSheet';
    case FinancialYear          = 'financialYear';
    case LedgerAccount          = 'ledgerAccount';
    case PersonalAccountingCode = 'personalAccountingCode';
    case PaymentMethod          = 'paymentMethod';
    case PaymentRun             = 'paymentRun';
    case PaymentRunItem         = 'paymentRunItem';
    case SepaDirectDebitMandate = 'sepaDirectDebitMandate';

    /** @beta */
    case StagedMoneyTransaction = 'stagedMoneyTransaction';

    case TermOfPayment          = 'termOfPayment';
    case Rebate                 = 'rebate';
    case Tax                    = 'tax';
    case TaxDeterminationRule   = 'taxDeterminationRule';
    case NumberRange            = 'numberRange';
    case NumberRangeValue       = 'numberRangeValue';
    case CostCenter             = 'costCenter';
    case CostCenterGroup        = 'costCenterGroup';
    case CostType               = 'costType';
    case PriceCalculationParameter = 'priceCalculationParameter';
    case Currency               = 'currency';
    case CommercialLanguage     = 'commercialLanguage';

    // -------------------------------------------------------------------------
    // HR / Time Tracking
    // -------------------------------------------------------------------------

    case Attendance         = 'attendance';

    /** @beta */
    case AbsenceType        = 'absenceType';

    case PerformanceRecord  = 'performanceRecord';
    case PersonDepartment   = 'personDepartment';
    case PersonRole         = 'personRole';
    case TimeRecord         = 'timeRecord';
    case ServiceQuota       = 'serviceQuota';

    /** @beta */
    case BusinessHolidays  = 'businessHolidays';

    /** @beta */
    case BusinessHours     = 'businessHours';

    // -------------------------------------------------------------------------
    // Tasks / Projects
    // -------------------------------------------------------------------------

    case Task                  = 'task';
    case TaskList              = 'taskList';
    case TaskTemplate          = 'taskTemplate';

    /** @beta */
    case TaskTopic             = 'taskTopic';

    /** @beta */
    case TaskType              = 'taskType';

    case PlaceOfService        = 'placeOfService';
    case ProjectOrderStatusPage = 'projectOrderStatusPage';

    // -------------------------------------------------------------------------
    // Communication / Messaging
    // -------------------------------------------------------------------------

    case ArchivedEmail     = 'archivedEmail';
    case MailTemplate      = 'mailTemplate';

    /** @beta */
    case MailAccount       = 'mailAccount';

    case Comment           = 'comment';
    case Notification      = 'notification';
    case Reminder          = 'reminder';
    case RecordEmailingRule = 'recordEmailingRule';

    /** @beta */
    case TextModule        = 'textModule';

    case Tag               = 'tag';
    case Translation       = 'translation';
    case CalendarEvent     = 'calendarEvent';
    case Calendar          = 'calendar';

    // -------------------------------------------------------------------------
    // Users / System Settings
    // -------------------------------------------------------------------------

    case User           = 'user';
    case UserRole       = 'userRole';

    /** @beta */
    case AppSettings    = 'appSettings';

    /** @beta */
    case AppUserSettings = 'appUserSettings';

    /** @beta */
    case ActivityFeedEntry = 'activityFeedEntry';

    /** @beta */
    case GroupwareContact = 'groupwareContact';

    // -------------------------------------------------------------------------
    // Miscellaneous / Reference data
    // -------------------------------------------------------------------------

    case CustomAttributeDefinition = 'customAttributeDefinition';
    case CustomsTariffNumber       = 'customsTariffNumber';
    case ExternalConnection        = 'externalConnection';
    case Region                    = 'region';
    case Title                     = 'title';
    case Unit                      = 'unit';
    case RemotePrintJob            = 'remotePrintJob';
    case TicketPoolingGroup        = 'ticketPoolingGroup';

    /** Meta: fires when a webhook subscription itself is created, updated or deleted. */
    case Webhook   = 'webhook';

    /** weclapp platform / OS-level events. */
    case WeclappOs = 'weclappOs';

    // -------------------------------------------------------------------------
    // Module-specific (may not be available in all tenants)
    // -------------------------------------------------------------------------

    /**
     * Service ticket. Not in the verified 117-entry list; may require the
     * Ticket module. Retained from a previous library version.
     */
    case Ticket = 'ticket';

    /**
     * Contract. Not in the verified 117-entry list; may require the
     * Contract module. Retained from a previous library version.
     */
    case Contract = 'contract';
}
