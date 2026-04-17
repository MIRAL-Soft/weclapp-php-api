# Changelog

All notable changes to this library are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [Unreleased] — Branch `WeclappAPIv2`

### Added — Line Item DTOs

- **`SalesOrderItemDTO`** — typed DTO for `salesOrderItem` entries embedded in `SalesOrderDTO::$orderItems`.
  Mirrors the full `salesOrderItem` schema from the weclapp OpenAPI spec:
  article reference, quantities, unit/pricing, computed amounts (gross/net incl. company-currency
  variants), fulfillment state (shipped, shippedQuantity, invoicedQuantity, returnedQuantity),
  item classification (itemType, invoicingType, positionNumber, groupName, parentItemId),
  manual-override flags, service-period and planning dates, and nested arrays
  (commissionSalesPartners, picks, tasks, reductionAdditionItems, customAttributes).
  Decimal values stored as `?string` to preserve API precision; convenience getters
  (`getQuantity()`, `getUnitPrice()`, `getNetAmount()`, `getGrossAmount()`) return `?float`.
  Helper methods: `isFreeText()`, `isService()`.

- **`SalesInvoiceItemDTO`** — typed DTO for `salesInvoiceItem` entries embedded in `SalesInvoiceDTO::$salesInvoiceItems`.
  Full `salesInvoiceItem` schema including accounting fields (`accountId`, `costTypeId`, `cost2CostCenterId`),
  credit-note linkage (`creditedInvoiceItemId`, `contractItemId`), serial numbers, cost centre items,
  and delivery/shipping dates.
  Helper methods: `isFreeText()`, `isCreditNoteItem()`.

- **`ItemType` enum** — four values from the weclapp API:
  `DEFAULT`, `FREE_TEXT`, `SERVICE`, `SERVICE_QUOTA`.
  Applies to both `SalesOrderItemDTO` and `SalesInvoiceItemDTO`.

- **`InvoicingType` enum** — two values from the weclapp API:
  `EFFORT`, `FIXED_PRICE`.
  Applies to `SalesOrderItemDTO` service positions.

### Changed — Line Item Hydration

- **`SalesOrderDTO::$orderItems`** — type changed from `list<array>` (raw) to `list<SalesOrderItemDTO>`.
  Hydration is automatic: `fromArray()` maps each raw item array through `SalesOrderItemDTO::fromArray()`.
  Existing code reading `$order->orderItems` continues to work; array indexing still works.
  Code that relied on raw associative arrays must be updated to use DTO property access.

- **`SalesInvoiceDTO`** — property renamed from `$invoiceItems` to `$salesInvoiceItems` to match
  the API key (`salesInvoiceItems`). Type changed from `list<array>` to `list<SalesInvoiceItemDTO>`.
  **Breaking change** — any code referencing `$invoice->invoiceItems` must be updated to
  `$invoice->salesInvoiceItems`.

- **`SalesOrderDTO`** — added `getShippingDate()` convenience method.

---

### Added — Document Endpoint Support

- **`DocumentDTO`** — DTO for the `/api/v2/document` response.
  Fields: `id`, `name`, `documentType`, `description`, `entityId`, `entityName`, `documentSize`,
  `mimeType`, `createdDate`, `lastModifiedDate`, `versions` (`list<DocumentVersionDTO>`).
  Helper methods: `isCancellationInvoice()`, `isSalesInvoice()`, `getFileName()`, `getLatestVersion()`.

- **`DocumentVersionDTO`** — DTO for individual version history entries inside a `DocumentDTO`.
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `comment`,
  `documentSize`, `documentVersion`, `userId`.

- **`DocumentType` enum** — 54 document-type constants mirroring the weclapp API.
  Key values:
  - `SalesInvoice` = `SALES_INVOICE`
  - `SalesInvoiceCancellation` = `SALES_INVOICE_CANCELLATION` (cancellation invoice PDF)

- **`DocumentResource`** — new resource registered as `$client->documents()`.
  Methods:
  - `findByEntity(string $entityId, string $entityName, ?QueryBuilder $extra): array<DocumentDTO>` — list all attachments on any entity
  - `findByEntityAndType(string $entityId, string $entityName, DocumentType $type): array<DocumentDTO>`
  - `findCancellationDocument(string $salesInvoiceId): ?DocumentDTO` — find the `SALES_INVOICE_CANCELLATION` attachment
  - `find(string $id): DocumentDTO` — fetch a single document (ID is URL-encoded automatically)
  - `download(string $id): string` — download binary content; handles compound IDs like `salesInvoice.926104.926166`
  - `downloadCancellationInvoice(string $salesInvoiceId): ?string` — combined lookup + download
  - `upload(string $entityId, string $entityName, string $name, string $binary, ?DocumentType $type, string $description, string $contentType): DocumentDTO`
  - `uploadVersion(string $id, string $binary, string $comment, string $contentType): DocumentDTO`
  - `update(string $id, array $data): DocumentDTO`
  - `delete(string $id): void`

- **`HttpClient::postUpload()`** — new method for raw binary uploads with custom `Content-Type` header.
  Used internally by `DocumentResource::upload()` and `DocumentResource::uploadVersion()`.

- **`WeclappClient::documents()`** — factory method to create a `DocumentResource` instance.

- **`SalesInvoiceResource::getCancellationPdf(string $salesInvoiceId): ?string`** —
  convenience method that delegates to `DocumentResource::downloadCancellationInvoice()`.
  Internally instantiates a `DocumentResource` via the shared `HttpClient` and `RateLimiter`.

---

### Added — Sales Invoice Enhancements

- **`SalesInvoiceType` enum** — seven values matching the weclapp API:
  `STANDARD_INVOICE`, `CREDIT_NOTE`, `ADVANCE_PAYMENT_INVOICE`, `FINAL_INVOICE`,
  `PART_PAYMENT_INVOICE`, `PREPAYMENT_INVOICE`, `RETAIL_INVOICE`.

- **`SalesInvoiceDTO`** — six new fields:
  - `salesInvoiceType` (`string`) — invoice type; `CREDIT_NOTE` identifies cancellation invoices
  - `precedingSalesInvoiceId` (`?string`) — for credit notes: ID of the original cancelled invoice
  - `cancellationNumber` (`?string`) — for cancelled invoices: CLX-number of the cancellation
  - `paid` (`bool`) — `true` if fully paid
  - `paymentStatus` (`?string`) — payment status string (e.g. `OPEN`, `PAID`, `CLEARED_WITH_CREDIT_NOTE`)
  - `bookingDate` (`?int`) — accounting booking date in epoch milliseconds

  New helper methods:
  - `isCreditNote(): bool` — `true` if `salesInvoiceType === 'CREDIT_NOTE'`
  - `getBookingDate(): ?DateTimeImmutable`
  - `getCustomerDisplayName(): string` — best available inline name
  - `isOpen(): bool` — `true` if `openAmount > 0`

- **`SalesInvoiceResource::findCreditNotes(?QueryBuilder $extra): array`** —
  fetches all invoices where `salesInvoiceType = CREDIT_NOTE`, sorted by creation date descending.

- **`SalesInvoiceResource::findCreditNotesModifiedSince(\DateTimeInterface|int $since): array`** —
  delta-sync shorthand for credit notes.

---

### Fixed

- **`SalesInvoiceStatus` enum** — previous values (`DRAFT`, `OPEN`, `PAID`, `OVERDUE`, `CREDITED`)
  were wrong. Corrected to the actual API values:
  `NEW`, `DOCUMENT_CREATED`, `OPEN_ITEM_CREATED`, `ENTRY_COMPLETED`, `CANCELLED`.

- **`SalesInvoiceDTO` item hydration** — `fromArray()` was reading from key `'invoiceItems'`
  but the API returns `'salesInvoiceItems'`. Fixed to use the correct key.

---

## [1.0.0] — Initial Release (weclapp API v1 → v2 Migration)

### Added

- `WeclappClient` — central factory/entry point with PSR-16 cache and PSR-3 logger injection
- `WeclappConfig` — immutable configuration VO; `fromEnv()` and `fromArray()` factory methods
- `AbstractResource` — base class with full CRUD, `listAll()`, `cursor()`, `findModifiedSince()`, `findCreatedSince()`
- `AbstractDTO` — base class with `toArray()` via reflection, typed helper methods
- `RateLimiter` — automatic retry with exponential back-off for HTTP 429 / 5xx
- `QueryBuilder` — fluent DSL for weclapp v2 filter syntax
- Resources: `CustomerResource`, `ContactResource`, `SupplierResource`, `ArticleResource`,
  `ArticleCategoryResource`, `SalesOrderResource`, `SalesInvoiceResource`, `QuotationResource`,
  `PartyResource`, `WebhookResource`
- DTOs: `CustomerDTO`, `ContactDTO`, `SupplierDTO`, `ArticleDTO`, `ArticleCategoryDTO`,
  `SalesOrderDTO`, `SalesInvoiceDTO`, `QuotationDTO`, `PartyDTO`, `WebhookDTO`, `PaginatedResultDTO`
- Enums: `SalesOrderStatus`, `QuotationStatus`, `WebhookEventType`
- `WebhookValidator` — HMAC-SHA256 signature verification
- `ValidationException` — typed exception for HTTP 400 weclapp v2 validation errors
- Legacy v1 wrappers retained as `@deprecated` for backward compatibility

[Unreleased]: https://github.com/MIRAL-Soft/weclapp-php-api/compare/HEAD...WeclappAPIv2
