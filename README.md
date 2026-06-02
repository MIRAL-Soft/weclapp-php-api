# weclapp PHP API Client

A professional PHP client library for the **weclapp REST API v2**.
Provides a clean, typed and modular interface to work with customers, contacts, suppliers,
articles, sales orders, invoices, quotations and webhooks — without writing a single raw HTTP call.

> **Version 2.0** — migrated from weclapp API v1 to v2.
> Legacy v1 classes are retained as deprecated wrappers for backward compatibility.

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | `^8.3` |
| weclapp API | `v2` |
| Guzzle | `^7.8` (auto-installed) |

---

## Installation

```bash
composer require miralsoft/weclapp-api
```

---

## Quick Start

```php
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;

// 1. Configure — replace with your own tenant and token
$config = new WeclappConfig(
    tenant: 'miralsoft',   // your subdomain: https://miralsoft.weclapp.com
    token:  'your-api-token-here'
);

// 2. Create the client
$client = new WeclappClient($config);

// 3. Use it
$customers = $client->customers()->listAll();

foreach ($customers as $customer) {
    echo $customer->customerNumber . ' — ' . $customer->getDisplayName() . PHP_EOL;
}
```

---

## Configuration

`WeclappConfig` is an immutable value object. All parameters are set once at construction time.

### Constructor

```php
use miralsoft\weclapp\api\Config\WeclappConfig;

$config = new WeclappConfig(
    tenant:         'miralsoft',  // Subdomain of your weclapp instance
    token:          'your-token', // API token from weclapp user settings
    version:        'v2',         // API version — default: 'v2'
    timeout:        30,           // HTTP request timeout in seconds — default: 30
    connectTimeout: 10,           // TCP connect timeout in seconds — default: 10
    maxRetries:     3,            // Retries on HTTP 429 / 5xx errors — default: 3
);

// Generated base URL:
// https://miralsoft.weclapp.com/webapp/api/v2/
echo $config->getBaseUrl();
```

### From Environment Variables

```php
// Reads WECLAPP_TENANT and WECLAPP_TOKEN (required).
// Optional: WECLAPP_VERSION, WECLAPP_TIMEOUT, WECLAPP_CONNECT_TIMEOUT, WECLAPP_MAX_RETRIES
$config = WeclappConfig::fromEnv();
```

### From Array (e.g. from a config file)

```php
$config = WeclappConfig::fromArray([
    'tenant'         => 'miralsoft',
    'token'          => 'your-token',
    'timeout'        => 60,
    'connectTimeout' => 5,
    'maxRetries'     => 5,
]);
```

> **Security:** The token is never exposed in `var_dump()` or `print_r()` output.
> `$config->__debugInfo()` returns `***REDACTED***` for the token field.

---

## Available Resources

| Method | Endpoint | Description |
|---|---|---|
| `$client->customers()` | `/customer` | Customers (organisations & persons) |
| `$client->contacts()` | `/contact` | Contact persons linked to customers |
| `$client->suppliers()` | `/supplier` | Suppliers |
| `$client->articles()` | `/article` | Products / articles |
| `$client->articleCategories()` | `/articleCategory` | Article category tree |
| `$client->salesOrders()` | `/salesOrder` | Sales orders + PDF download |
| `$client->salesInvoices()` | `/salesInvoice` | Sales invoices + PDF download |
| `$client->quotations()` | `/quotation` | Quotations + PDF + order conversion |
| `$client->purchaseOrders()` | `/purchaseOrder` | Purchase orders + PDF download |
| `$client->shipments()` | `/shipment` | Shipments + delivery note / label PDFs |
| `$client->tickets()` | `/ticket` | Support tickets |
| `$client->documents()` | `/document` | File attachments on any entity — query, download, upload |
| `$client->parties()` | `/party` | Party identity lookup (resolves partyId to name/number) |
| `$client->numberRanges()` | `/numberRange` | Number range config — read-only |
| `$client->numberRangeValues()` | `/numberRangeValue` | Number range counters and prefixes — read-only |
| `$client->webhooks()` | `/webhook` | Event-driven webhook subscriptions |

---

## CRUD Operations

Every resource supports the full set of CRUD operations:

```php
$customers = $client->customers();

// Count
$total = $customers->count();

// Read single record by ID
$customer = $customers->find('abc-123');

// Paginated list
$page = $customers->list(
    QueryBuilder::new()->page(1)->pageSize(50)->sort('company')
);

// All records (auto-pagination)
$all = $customers->listAll();

// Create
$newCustomer = $customers->create([
    'company'   => 'Acme GmbH',
    'partyType' => 'ORGANIZATION',
    'email'     => 'info@acme.de',
]);

// Update (include version for optimistic locking)
$updated = $customers->update('abc-123', [
    'id'      => 'abc-123',
    'version' => '3',
    'phone'   => '+49 30 123456',
]);

// Delete
$customers->delete('abc-123');
```

---

## Filtering & Sorting

Use the fluent `QueryBuilder` to compose filters using the weclapp v2 filter syntax:

```php
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Query\FilterOperator;

$result = $client->customers()->list(
    QueryBuilder::new()
        ->filterEq('active', true)                    // active-eq=true
        ->filterIlike('company', 'acme')              // company-ilike=acme
        ->filterGt('createdDate', 1711400000000)      // createdDate-gt=...
        ->sort('company')                             // sort=company
        ->sort('customerNumber', 'desc')              // sort=company,-customerNumber
        ->page(1)
        ->pageSize(50)
);

// Available shorthand methods:
// ->filterEq()    equals
// ->filterNeq()   not equals
// ->filterIlike() case-insensitive contains (LIKE %value%)
// ->filterGt()    greater than
// ->filterGte()   greater than or equal
// ->filterLt()    less than
// ->filterLte()   less than or equal
// ->filterIn()    value in list
// ->filter($field, FilterOperator::IS_NULL)   field is null
```

---

## Paginated Results

`list()` returns a `PaginatedResultDTO` with full pagination metadata:

```php
$result = $client->articles()->list(QueryBuilder::new()->page(1)->pageSize(100));

echo $result->total;     // Total matching records across all pages
echo $result->page;      // Current page number
echo $result->pageSize;  // Items per page
echo $result->hasMore;   // true if more pages exist
echo $result->count();   // Items on this page

// Manually paginate
$page = 1;
do {
    $result = $client->articles()->list(QueryBuilder::new()->page($page)->pageSize(100));
    foreach ($result->items as $article) {
        // process $article (ArticleDTO)
    }
    $page++;
} while ($result->hasMore);
```

---

## Delta Sync — Fetch Only Changed Records

A key feature for integrating weclapp with external systems (e.g. ticket systems, CRMs, shops).
Instead of loading all records on every run, fetch only what changed since the last sync:

```php
// --- First run: load everything and remember the timestamp ---
$allCustomers = $client->customers()->listAll();
$lastSyncMs   = time() * 1000; // store this in your database or a file

// --- Every subsequent run: fetch only changed records ---
$changedCustomers = $client->customers()->findModifiedSince($lastSyncMs);

foreach ($changedCustomers as $customer) {
    // Sync to your external system
    $mySystem->updateContact($customer->id, [
        'name'  => $customer->getDisplayName(),
        'email' => $customer->email,
        'phone' => $customer->phone,
    ]);

    // Advance the sync cursor to the latest change
    $lastSyncMs = max($lastSyncMs, $customer->lastModifiedDate);
}

// Save $lastSyncMs for the next run

// You can also use a DateTime object:
$changed = $client->contacts()->findModifiedSince(new DateTime('-1 hour'));

// Or combine with extra filters:
$changed = $client->customers()->findModifiedSince(
    since: $lastSyncMs,
    extra: QueryBuilder::new()->filterEq('active', true)
);
```

> All DTOs expose `lastModifiedDate` (epoch ms), `getLastModifiedAt()` (DateTimeImmutable),
> `createdDate` and `getCreatedAt()` for this purpose.

> **Millisecond-precision watermarks:** `modifiedSince()` and `createdSince()` accept
> either a raw epoch-ms integer **or** a `DateTimeInterface` object. When a `DateTime`/
> `DateTimeImmutable` is passed, the conversion to epoch ms preserves the sub-second
> component (milliseconds). This matters for reliable delta sync: weclapp timestamps
> include ms precision, and a watermark rounded down to whole seconds will re-fetch
> the last record on every run.
>
> ```php
> // Safe: integer epoch ms round-trips exactly
> $lastSyncMs = max($lastSyncMs, $customer->lastModifiedDate + 1);
> $changed = $client->customers()->findModifiedSince($lastSyncMs);
>
> // Also safe: DateTimeImmutable with ms precision
> $dt = DateTimeImmutable::createFromFormat('U.u',
>     sprintf('%d.%03d', intdiv($lastSyncMs, 1000), $lastSyncMs % 1000));
> $changed = $client->customers()->findModifiedSince($dt); // exact ms preserved
> ```

---

## Entity-Specific Features

### Customers

```php
$customers = $client->customers();

// Convenience lookups
$customer = $customers->findByCustomerNumber('K-10042');
$results  = $customers->findByCompany('Acme');       // case-insensitive search
$results  = $customers->findByEmail('info@acme.de');

// Search by name — works for both ORGANIZATION (company name) and PERSON (last name)
$results  = $customers->findByName('Smith');
// → matches "Smith Ltd." (company) and "John Smith" (person)

// Display name — uses partyType, not the company field, to distinguish cases:
// ORGANIZATION → company ?? customerNumber; PERSON → "firstName lastName" ?? customerNumber
echo $customer->getDisplayName();

// Correct API field names (party schema, 137 fields total)
echo $customer->mobilePhone1;           // mobile (API key: mobilePhone1)
echo $customer->customerNumber;         // e.g. "K-10042"
echo $customer->vatIdentificationNumber; // VAT ID (was: vatRegistrationNumber)
echo $customer->customerTermOfPaymentId; // payment terms (was: paymentTermId)
echo $customer->customerSalesChannel;    // sales channel (was: salesChannel)
if ($customer->customerBlocked) { ... }  // customer blocked (was: blocked)

// Typed nested arrays
foreach ($customer->addresses as $address) {       // list<AddressDTO>
    echo $address->street1 . ', ' . $address->zipcode;
}
foreach ($customer->bankAccounts as $bank) {       // list<BankAccountDTO>
    echo $bank->iban;
}
```

### Contacts

weclapp embeds contacts in a customer response as stubs — `[{"id": "975300"}]` — with no
other fields populated. Use `loadFromStubs()` to resolve them to full `ContactDTO` objects:

```php
// Load a customer — contacts come back as stubs [{"id": "..."}, ...]
$customer = $client->customers()->find($customerId);

// Resolve stubs → full ContactDTO objects (one find() call per contact)
$contacts = $client->contacts()->loadFromStubs($customer->contacts);

foreach ($contacts as $contact) {
    echo $contact->getDisplayName();    // "Max Mustermann" — consistent with other party DTOs
    echo $contact->getFullName();       // identical — alias kept for backward compatibility
    echo $contact->email;
    echo $contact->mobilePhone1;        // mobile (API key: mobilePhone1)
    echo $contact->personRoleId;        // job role ID
    echo $contact->personDepartmentId;  // department ID
}

// Find by email (server-side filter — works independently of stubs)
$byEmail = $client->contacts()->findByEmail('max@acme.de');
```

> **Why not filter by parentPartyId?**
> The weclapp API returns contact objects with `parentPartyId: null` even for linked
> contacts — the `parentPartyId-eq` filter always returns zero results. `loadFromStubs()`
> using the IDs from the customer's embedded `contacts` array is the only reliable way
> to load a customer's contacts.

> **Deprecated methods:** `findByParentPartyId()` (broken — see above) and
> `findByCustomer()` (wrong filter field) are both `@deprecated` and will be removed
> in a future major release.

### Articles

```php
$articles = $client->articles();

// Find by article number (SKU)
$article = $articles->findByArticleNumber('ART-001');

// Convenience: get the category ID for a given SKU without loading the full DTO
// Returns null when no category is assigned; throws NotFoundException if article missing
$categoryId = $articles->findCategoryIdByNumber('ART-001');

// Find all articles in a category
$articles = $articles->findByCategory($categoryId);

// Find only in-stock articles (server-side filter via API)
$inStock  = $articles->findInStock();

// Core fields
echo $article->articleNumber;
echo $article->name;
echo $article->longText;       // long HTML description (API key: longText)
echo $article->unitId;         // unit of measure ID (resolve via /unit/{id})
echo $article->articleCategoryId;

// BOM check
if ($article->isBillOfMaterial()) {
    foreach ($article->salesBillOfMaterialItems as $component) {
        echo $component->articleId . ' × ' . $component->getQuantity() . PHP_EOL;
    }
}

// Main image
$image = $article->getMainImage();
echo $image?->fileName;

// Prices (customer/channel/scale-specific entries)
foreach ($article->articlePrices as $price) {
    echo $price->getPrice() . ' ' . $price->currencyId . PHP_EOL;
}

// ── Full raw backup (findRaw) ─────────────────────────────────────────────────
//
// findRaw() returns the complete, unfiltered API response as a plain array —
// every field including read-only system fields not covered by the DTO.
// Use it to take a snapshot before a write operation.

$backup = $client->articles()->findRaw($article->id);

// Restore after a bad write (merge the current version first):
$current = $client->articles()->findRaw($article->id);
$client->articles()->update($article->id, array_merge($backup, ['version' => $current['version']]));

// ── Partial update (patch) ────────────────────────────────────────────────────
//
// patch() changes only the fields you supply. All other fields (description,
// category, prices, EAN, custom attributes, …) are preserved automatically
// via an internal Read-Modify-Write (GET → merge → PUT).
//
// Use this instead of update() whenever you only need to touch one or a few
// fields — update() sends exactly what you provide and weclapp treats absent
// fields as "reset to null".

// Rename an article without touching anything else
$renamed = $client->articles()->patch($article->id, [
    'name' => trim(preg_replace('/\s+/', ' ', $article->name)), // remove excess whitespace
]);

// Multiple fields in one call
$client->articles()->patch($article->id, [
    'name'   => 'Corrected Name',
    'active' => false,
]);

// Dry-run: validate the patch without persisting
$preview = $client->articles()->withDryRun()->patch($article->id, [
    'name' => 'Preview Name',
]);
// $preview->id === '' (not saved), $preview->name reflects the submitted value
```

### Article Categories

```php
$categories = $client->articleCategories();

// Find by name (exact match)
$category = $categories->findByName('Electronics');

// Find only root categories (no parent)
$roots = $categories->findRootCategories();

echo $category->isRootCategory() ? 'Root' : 'Sub-category';
echo $category->parentCategoryId; // ID of parent (null for root)
echo $category->description;
```

### Sales Orders

```php
$orders = $client->salesOrders();

// Find all orders for a customer
$orders = $orders->findByCustomer($customerId);

// Find by status
$open = $orders->findByStatus('ORDER_CONFIRMED');

// Find by human-readable order number (throws NotFoundException if not found)
$order = $client->salesOrders()->findByOrderNumber('SO-10042');

// Download order confirmation PDF
$pdf = $orders->getPdf($orderId);
file_put_contents('order-confirmation.pdf', $pdf);

// Date helpers
echo $order->getOrderDate()?->format('d.m.Y');
echo $order->getDeliveryDate()?->format('d.m.Y');
echo $order->getShippingDate()?->format('d.m.Y');

// Line items — orderItems is a typed list<SalesOrderItemDTO>
foreach ($order->orderItems as $item) {
    echo $item->positionNumber . '. ' . $item->title . PHP_EOL;
    echo '   Article ID : ' . $item->articleId . PHP_EOL;
    echo '   Qty        : ' . $item->getQuantity() . ' (unit: ' . $item->unitId . ')' . PHP_EOL;
    echo '   Unit price : ' . $item->getUnitPrice() . PHP_EOL;
    echo '   Net amount : ' . $item->getNetAmount() . PHP_EOL;
    echo '   Shipped    : ' . ($item->shipped ? 'yes' : 'no') . PHP_EOL;
}

// Mutate line items (Read-Modify-Write — version is handled automatically)
//
// weclapp has no dedicated item endpoint: items are embedded in the order
// and written back via PUT. All three methods fetch the order first, mutate
// the item list, then PUT the order back with its current version to satisfy
// optimistic locking. If another process modified the order concurrently,
// an OptimisticLockException is thrown — re-fetch and retry.

// Add a new item (requires at least "articleId" or "title")
$updated = $client->salesOrders()->addOrderItem($orderId, [
    'articleId' => 'art-42',
    'quantity'  => '2.00',
]);

// Update an existing item (only the fields you pass are changed)
$updated = $client->salesOrders()->updateOrderItem($orderId, $itemId, [
    'quantity'  => '5.00',
    'unitPrice' => '8.50',
]);

// Remove an item
$updated = $client->salesOrders()->removeOrderItem($orderId, $itemId);
```

### Sales Invoices

```php
$invoices = $client->salesInvoices();

// Find open (unpaid) invoices
$open = $invoices->findOpen();

// Find by human-readable invoice number (throws NotFoundException if not found)
// Works for all invoice types: regular (RE-*), credit notes (CLX-*), proforma (PR-*)
$invoice = $client->salesInvoices()->findByInvoiceNumber('RE-10042');

// Download invoice PDF
$pdf = $invoices->getPdf($invoiceId);
file_put_contents('invoice.pdf', $pdf);

// Amounts — stored as decimal strings to preserve API precision; use helpers for float
echo $invoice->netAmount;          // e.g. "1234.56" (?string)
echo $invoice->getNetAmount();     // 1234.56 (?float)
echo $invoice->getGrossAmount();   // 1469.13 (?float)

// Dates
echo $invoice->getInvoiceDate()?->format('d.m.Y');
echo $invoice->getDueDate()?->format('d.m.Y');
echo $invoice->getBookingDate()?->format('d.m.Y');

// Credit notes (cancellation invoices)
if ($invoice->isCreditNote()) {
    echo 'Cancellation of: ' . $invoice->precedingSalesInvoiceId;
}

// Line items — salesInvoiceItems is a typed list<SalesInvoiceItemDTO>
foreach ($invoice->salesInvoiceItems as $item) {
    echo $item->positionNumber . '. ' . $item->title . PHP_EOL;
    echo '   Net amount : ' . $item->getNetAmount() . PHP_EOL;
    echo '   Tax ID     : ' . $item->taxId . PHP_EOL;

    if ($item->isCreditNoteItem()) {
        echo '   Cancels item: ' . $item->creditedInvoiceItemId . PHP_EOL;
    }
}
```

### Documents

Documents are file attachments that belong to weclapp entities (invoices, orders, customers, etc.).
They are **not** embedded inside those entities — they must be queried separately using `entityId` + `entityName`.

```php
$docs = $client->documents();

// List all documents attached to an invoice
$attachments = $docs->findByEntity($invoiceId, 'salesInvoice');
foreach ($attachments as $doc) {
    echo $doc->documentType . ' — ' . $doc->name . ' (' . $doc->documentSize . ' bytes)' . PHP_EOL;
}

// Filter by document type
use miralsoft\weclapp\api\Enum\DocumentType;

$cancellationDocs = $docs->findByEntityAndType(
    $invoiceId, 'salesInvoice', DocumentType::SalesInvoiceCancellation
);

// Fetch a single document by ID (ID format: "salesInvoice.926104.926166")
$doc = $docs->find('salesInvoice.926104.926166');

// Download the binary content of a document (ID is URL-encoded automatically)
$pdf = $docs->download($doc->id);
file_put_contents($doc->getFileName(), $pdf);

// Upload a new document and attach it to an entity
$pdfBytes = file_get_contents('/path/to/file.pdf');
$newDoc = $docs->upload(
    entityId:    $invoiceId,
    entityName:  'salesInvoice',
    name:        'manually-added.pdf',
    binary:      $pdfBytes,
    type:        DocumentType::SalesInvoice,
    contentType: 'application/pdf',
);

// Upload a new version of an existing document
$updatedDoc = $docs->uploadVersion(
    id:          $doc->id,
    binary:      $pdfBytes,
    comment:     'Corrected amount',
    contentType: 'application/pdf',
);

// Update document metadata (name, type, description)
$updated = $docs->update($doc->id, [
    'name'         => 'renamed.pdf',
    'documentType' => DocumentType::SalesInvoiceDefault->value,
    'description'  => 'Manually renamed',
]);

// Delete a document
$docs->delete($doc->id);
```

### Cancellation Invoices (Credit Notes)

Cancellation invoices are **not** separate `salesInvoice` records in weclapp.
They exist as a **document attachment** (`SALES_INVOICE_CANCELLATION`) on the original cancelled invoice.
The document filename contains the CLX-number (e.g. `Storno-CLX1061-R-RE26767-Kdnr-12070.pdf` — weclapp-generated).

```php
// Recommended approach: via salesInvoices() — encapsulates the document lookup internally
$invoices = $client->salesInvoices();

// Fetch all cancelled invoices
$cancelled = $invoices->listAll(
    QueryBuilder::new()->filterEq('status', 'CANCELLED')
);

foreach ($cancelled as $invoice) {
    // cancellationNumber holds the CLX-number, e.g. "CLX-1061"
    if ($invoice->cancellationNumber === null) {
        continue;
    }

    // Download the cancellation invoice PDF (automatically finds the SALES_INVOICE_CANCELLATION document)
    $pdf = $invoices->getCancellationPdf($invoice->id);

    if ($pdf !== null) {
        file_put_contents($invoice->cancellationNumber . '.pdf', $pdf);
    }
}

// Alternative: directly via the document endpoint for more control
$doc = $client->documents()->findCancellationDocument($invoiceId);
if ($doc !== null) {
    echo $doc->name;         // weclapp-generated filename, e.g. "Storno-CLX1061-R-RE26767-Kdnr-12070.pdf"
    echo $doc->documentSize; // file size in bytes
    $pdf = $client->documents()->download($doc->id);
}
```

**Relationship between original invoice and cancellation:**

| Field | On | Value |
|---|---|---|
| `status` | Original invoice | `CANCELLED` |
| `cancellationNumber` | Original invoice | CLX-number, e.g. `CLX-1061` |
| `documentType` | Document attachment | `SALES_INVOICE_CANCELLATION` |
| `name` | Document attachment | Filename including CLX-number (weclapp-generated) |

### Quantity Units

Quantity units (Mengeneinheiten) define the unit of measure used on order and
invoice line items (e.g. `h` for Stunde, `Stk.` for Stück, `Lizenz`).
Time-based units additionally carry a `timeUnitAmount` (in **seconds**) which
enables automated time-tracking sync workflows.

```php
$units = $client->quantityUnits();

// All units — useful for generic dropdowns
$all = $units->listAll();

// Only time-based units (those with a timeUnitAmount) — ideal for setup-UI dropdowns
// that power time-tracking reverse-sync (Docbee → Weclapp)
$timeUnits = $units->findTimeUnits();
foreach ($timeUnits as $unit) {
    echo $unit->description;          // "Stunde"
    echo $unit->name;                 // "h"
    echo $unit->timeUnitAmount;       // 3600  ← seconds (verified against live API)
    echo $unit->getMilliseconds();    // 3_600_000  ← ms, ready for msPerUnit config
    echo $unit->isTimeUnit();         // true
}

// Look up a specific unit by name
$hour = $units->findByName('h');
echo $hour->timeUnitAmount; // 3600

// Find by ID
$unit = $units->find('2221');
```

> **`timeUnitAmount` unit is seconds, not milliseconds** — verified against the
> live weclapp API: `Stunde` returns `3600` (not `3600000`). Use
> `getMilliseconds()` or multiply by 1000 for `msPerUnit` config fields.

> **Jahr / Monat do not have `timeUnitAmount`** — even though they are
> time-related, weclapp does not set this field for them. `findTimeUnits()`
> therefore excludes them. This is intentional: monthly/yearly billing cycles
> are not compatible with automated per-minute time rounding.

> **Fail-soft guard** (for consumers that check library version at runtime):
> ```php
> if (method_exists($client, 'quantityUnits')) {
>     $timeUnits = $client->quantityUnits()->findTimeUnits();
> }
> ```

**Available fields on `QuantityUnitDTO`:**

| Field | Type | Description |
|---|---|---|
| `id` | `string` | weclapp UUID |
| `version` | `string` | Optimistic locking version |
| `createdDate` | `int` | Creation timestamp (epoch ms) |
| `lastModifiedDate` | `int` | Last modification timestamp (epoch ms) |
| `name` | `string` | Short symbol, e.g. `h`, `Stk.`, `Lizenz` |
| `description` | `?string` | Long name, e.g. `Stunde`, `Stück` (max 60 chars) |
| `timeUnitAmount` | `?int` | Duration in **seconds**; `null` for non-time units |

### Custom Attributes (user-defined fields)

weclapp lets you define custom fields ("benutzerdefinierte Felder") per entity type.
The library manages both the **definitions** (the field schema) and the **values**
on each entity instance.

```php
use miralsoft\weclapp\api\DTO\CustomAttributeDTO;
use miralsoft\weclapp\api\Enum\CustomAttributeEntityType;
use miralsoft\weclapp\api\Enum\CustomAttributeType;

// ── 1. Ensure a definition exists (idempotent) ───────────────────────────────
$def = $client->customAttributeDefinitions()->ensure(
    CustomAttributeEntityType::SalesOrder,
    'docbeeTicketId',         // stable attributeKey (your handle)
    'Docbee Ticket ID',       // human-readable label
    CustomAttributeType::String,
);
// Second call returns the SAME definition — never duplicates.
$definitionId = $def->id;

// Build a setup-UI dropdown of all custom fields on an entity:
$fields = $client->customAttributeDefinitions()->findByEntity(CustomAttributeEntityType::SalesOrder);

// ── 2a. Write a value ATOMICALLY when creating the entity ─────────────────────
$order = $client->salesOrders()->create([
    'customerId'       => $customerId,
    'customAttributes' => [CustomAttributeDTO::string($definitionId, 'TICKET-4711')],
]);

// ── 2b. Or set it on an existing entity (preserves all other fields) ──────────
$client->salesOrders()->setCustomAttribute(
    $orderId,
    CustomAttributeDTO::string($definitionId, 'TICKET-4711'),
);

// ── 3. Read a value ──────────────────────────────────────────────────────────
$order  = $client->salesOrders()->find($orderId);
$ticket = $order->getCustomAttribute($definitionId)?->stringValue; // typed (recommended)
$value  = $order->getCustomAttributeValue($definitionId);          // best-effort scalar
```

**Typed payload builders** on `CustomAttributeDTO` (the library picks the correct
value field per type — you never assemble raw payloads):

| Builder | For attribute type |
|---|---|
| `CustomAttributeDTO::string($defId, $value)` | STRING, URL, LARGE_TEXT |
| `CustomAttributeDTO::number($defId, $value)` | INTEGER, DECIMAL |
| `CustomAttributeDTO::boolean($defId, $value)` | BOOLEAN |
| `CustomAttributeDTO::date($defId, $value)` | DATE (DateTime or epoch ms, ms-precise) |
| `CustomAttributeDTO::selection($defId, $valueId)` | LIST (single-select) |

`setCustomAttribute()` is on `AbstractResource`, so it works for **every** entity
(salesOrder, article, customer, …). It uses the same safe Read-Modify-Write strategy
as `patch()`: the full record is round-tripped, so no other field is cleared, and
optimistic locking + dry-run are handled automatically.

`getCustomAttribute()` / `getCustomAttributeValue()` work on any entity DTO that
maps `customAttributes` and return `null` when the attribute (or the property) is absent.

> **Definition management notes (live-verified):**
> - Entity scope is set via the `entities` array (`["salesOrder"]`), **not** an
>   `attributeEntityType` field — `ensure()` handles this for you.
> - `entities` is not server-side filterable, so `findByEntity()` / `findByKey()`
>   filter the entity scope client-side.
> - `attributeKey` is freely choosable and is the stable idempotency handle.
> - `customAttributes` are returned in both `find()` and list responses by default —
>   no field-selector needed.

> **`getCustomAttributeValue()` caveat:** a value object does not carry its own type,
> so the convenience reader returns the first populated field
> (string → number → date → selectedValueId → boolean). For known-type fields prefer
> the typed property, e.g. `getCustomAttribute($id)?->stringValue`.

### Number Ranges & Proforma Invoice Detection

weclapp assigns every document type its own number series (e.g. `RE-` for invoices, `CLX-` for
credit notes, `PR-` for proforma invoices). These prefixes are **tenant-configurable** via the
`/numberRange` and `/numberRangeValue` endpoints.

**Important:** Proforma invoices have **no dedicated `salesInvoiceType` value** in the weclapp API.
The `salesInvoiceType` enum only contains `STANDARD_INVOICE`, `CREDIT_NOTE`, etc. — never
`PROFORMA_INVOICE`. Proforma invoices are identified solely by their `invoiceNumber` prefix,
which comes from the `PROFORMA_INVOICE` number range configuration.

```php
use miralsoft\weclapp\api\Enum\NumberRangeType;

// --- Fetch the configured proforma prefix (two API calls, cache the result) ---
$prefix = $client->numberRanges()->getProformaInvoicePrefix();
// Returns "PR-" (or whatever the tenant has configured), or null if not set up.

// --- Exclude proforma invoices from a DATEV export ---
$allInvoices = $client->salesInvoices()->listAll();
$forDatev    = array_filter(
    $allInvoices,
    fn($inv) => $prefix === null || !str_starts_with($inv->invoiceNumber, $prefix)
);

// --- Look up any number range by type ---
$range = $client->numberRanges()->findByType(NumberRangeType::SalesInvoice);
echo $range?->type; // "SALES_INVOICE"

// --- Inspect the full counter configuration ---
$values = $client->numberRangeValues()->findByNumberRange($range->id);
foreach ($values as $value) {
    echo $value->prefix;                        // e.g. "RE-"
    echo $value->lastValue;                     // last issued number
    echo $value->formatNextNumber();            // e.g. "RE-10043"
    echo $value->isCurrentlyActive() ? 'active' : 'inactive';
    echo $value->getValidFrom()?->format('d.m.Y') . ' – ' . $value->getValidTo()?->format('d.m.Y');
}
```

> Cache `getProformaInvoicePrefix()` — the prefix almost never changes and the two API
> calls add unnecessary latency on every sync run.

### Parties

The `party` endpoint is the common base entity for customers, suppliers and contacts.
Use it to resolve a `partyId` reference (e.g. from a sales invoice) to identity data
without loading the full customer or supplier payload.

```php
$party = $client->parties()->find($invoice->partyId);

echo $party->customerNumber;   // e.g. "K-10042"
echo $party->getDisplayName(); // company name or "First Last"
echo $party->partyType;        // "ORGANIZATION" or "PERSON"
```

### Quotations

```php
$quotations = $client->quotations();

// Find all quotations for a customer
$list = $quotations->findByCustomer($customerId);

// Find by human-readable quotation number (throws NotFoundException if not found)
$quotation = $client->quotations()->findByQuotationNumber('ANG-10042');

// Check expiry
echo $quotation->isExpired() ? 'Expired' : 'Valid until: ' . $quotation->getValidUntil()?->format('d.m.Y');

// Download quotation PDF
$pdf = $quotations->getPdf($quotationId);

// Convert to sales order
$order = $quotations->convertToSalesOrder($quotationId);
echo $order->orderNumber; // e.g. "SO-10042"
```

### Webhooks

Webhooks let weclapp notify your application in real time when data changes —
no polling required.

```php
use miralsoft\weclapp\api\Enum\WebhookEntityName;

$webhooks = $client->webhooks();

// ── Idempotent setup ─────────────────────────────────────────────────────────
//
// ensureSubscription() is the recommended setup method. Call it on every
// application start — it creates the subscription only if it does not yet exist,
// returns the existing one unchanged if all flags are already set, or merges
// flags additively if a new event type needs to be added.
// No duplicate subscriptions are ever created.

$client->webhooks()->ensureSubscription(
    entityName: WebhookEntityName::SalesOrder->value,
    url:        'https://my-app.example.com/webhooks/weclapp',
    atCreate:   true,
    atUpdate:   true,
);

$client->webhooks()->ensureSubscription(
    entityName: WebhookEntityName::Customer->value,   // 'customer' — distinct from 'party'
    url:        'https://my-app.example.com/webhooks/weclapp',
    atCreate:   true,
    atUpdate:   true,
    atDelete:   true,
);

// ── One-shot registration (use register() when you control the lifecycle) ────

$webhook = $webhooks->register(
    entityName: WebhookEntityName::Party->value,      // 'party' covers all party types
    url:        'https://my-app.example.com/webhooks/weclapp',
    atCreate:   true,
    atUpdate:   true,
    atDelete:   true,
);

// ── Status check ─────────────────────────────────────────────────────────────

// All subscriptions pointing to a URL (useful for setup status pages)
$mine = $webhooks->findByUrl('https://my-app.example.com/webhooks/weclapp');
foreach ($mine as $hook) {
    echo $hook->entityName . ': ' . ($hook->isActive() ? 'active' : 'INACTIVE') . PHP_EOL;
    if ($hook->errorMessage !== null) {
        echo '  Last error: ' . $hook->errorMessage . PHP_EOL;
    }
}

// All subscriptions for a specific entity
$orderHooks = $webhooks->findByEntityName(WebhookEntityName::SalesOrder->value);

// ── Lifecycle ─────────────────────────────────────────────────────────────────

// Soft-deactivate (preserves the subscription; weclapp stops delivering events)
$webhooks->deactivate($webhook->id);

// Reactivate (clears deactivatedDate — see method docblock for caveats)
$webhooks->reactivate($webhook->id);

// Permanent deletion
$webhooks->delete($webhook->id);

// List all registered webhooks
$all = $webhooks->all();
```

> **Note on entityName values:**
> `customer`, `contact` and `party` are **distinct** entityNames — all three exist in parallel.
> There is **no** `salesOrderItem` — item changes fire as `salesOrder` update events
> containing the full order state with all items.
> See the `WebhookEntityName` enum for all 117+ verified values with `@beta` annotations.

---

## Error Handling

All errors throw typed exceptions. Catch `WeclappApiException` for a single catch-all,
or use specific subtypes for fine-grained handling:

```php
use miralsoft\weclapp\api\Exception\AuthenticationException;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\OptimisticLockException;
use miralsoft\weclapp\api\Exception\ValidationException;
use miralsoft\weclapp\api\Exception\RateLimitException;
use miralsoft\weclapp\api\Exception\ServerException;
use miralsoft\weclapp\api\Exception\WeclappApiException;

try {
    $customer = $client->customers()->find('non-existent-id');

} catch (AuthenticationException $e) {
    // HTTP 401 — invalid or missing API token
    echo 'Auth error: ' . $e->getMessage();

} catch (NotFoundException $e) {
    // HTTP 404 — record does not exist
    echo 'Not found: ' . $e->getMessage();

} catch (ValidationException $e) {
    // HTTP 400 — weclapp v2 strict validation failed
    foreach ($e->getErrors() as $error) {
        echo $error['field'] . ': ' . $error['message'] . PHP_EOL;
    }

} catch (OptimisticLockException $e) {
    // HTTP 409 — version conflict: record was changed by another process
    // Re-fetch the latest version and retry the update
    $fresh = $client->customers()->find($customerId);
    $client->customers()->update($customerId, [
        'version' => $fresh->version,
        'company' => 'New Name',
    ]);

} catch (RateLimitException $e) {
    // HTTP 429 — retries exhausted (automatic retry with backoff is built-in)
    echo 'Rate limited. Retry after: ' . $e->getRetryAfter() . 's';

} catch (ServerException $e) {
    // HTTP 5xx — weclapp server error (also auto-retried, see below)
    echo 'weclapp server error (HTTP ' . $e->getStatusCode() . ')';

} catch (WeclappApiException $e) {
    // Any other API error
    echo 'API error: ' . $e->getMessage();
    echo 'URL: '       . $e->getRequestUrl();
    echo 'Response: '  . $e->getResponseBody();
}
```

### Rate Limiting & 5xx Retry

HTTP 429 **and** HTTP 5xx responses are handled automatically with exponential backoff.
The delay is capped at 5 minutes per attempt regardless of the `Retry-After` value:

```
Attempt 1 → wait  Retry-After seconds  (or 1s for 5xx)
Attempt 2 → wait  Retry-After × 2 seconds
Attempt 3 → wait  Retry-After × 4 seconds
Attempt 4 → throws RateLimitException / ServerException
```

Configure the number of retries via `WeclappConfig`:

```php
$config = new WeclappConfig(tenant: 'miralsoft', token: 'token', maxRetries: 5);
```

---

## Dry-Run Mode

Every resource supports weclapp's built-in dry-run mechanism: append `?dryRun=true`
to a write request and weclapp validates the payload and runs business logic —
but **does not save anything**. Ideal for pre-flight validation before actually
committing data.

```php
// Validate a new order without creating it
$order = $client->salesOrders()->withDryRun()->create([
    'customerId' => 'cust-123',
    'orderItems' => [
        ['articleId' => 'art-1', 'quantity' => '2.00'],
    ],
]);
// $order is a fully populated SalesOrderDTO, but $order->id === '' (not saved)

// Test whether an update would pass validation
try {
    $client->customers()->withDryRun()->update($customerId, ['vatId' => 'INVALID']);
    echo 'Update would succeed.';
} catch (\miralsoft\weclapp\api\Exception\ValidationException $e) {
    echo 'Would fail: ' . implode(', ', $e->getErrors());
}

// Check whether a delete would be blocked by weclapp business rules
try {
    $client->articles()->withDryRun()->delete($articleId);
    echo 'Safe to delete.';
} catch (\miralsoft\weclapp\api\Exception\WeclappApiException $e) {
    echo 'Cannot delete: ' . $e->getMessage();
}
```

**Key points:**

| Aspect | Detail |
|---|---|
| **Trigger** | `->withDryRun()` on any resource — returns a clone, never mutates the original |
| **Affected methods** | `create()`, `update()`, `delete()` |
| **Unaffected methods** | All read operations (`find`, `list`, `listAll`, `cursor`, …) |
| **create / update** | HTTP 200 returned; DTO populated minus `id` / `version` / dates |
| **delete** | HTTP 200 returned; void returned as usual |
| **Errors** | Identical exceptions to real calls (`ValidationException`, `NotFoundException`, etc.) |
| **Scope** | `withDryRun()` clones the resource; original stays in normal mode |

```php
$res = $client->salesOrders()->withDryRun();
$res->isDryRun(); // true

// Re-disable on a clone:
$realRes = $res->withDryRun(false);
$realRes->isDryRun(); // false
```

---

## Caching

Inject a PSR-16 cache to avoid redundant `listAll()` calls.
Any PSR-16 compatible library works (Symfony Cache, Laravel Cache, etc.):

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

$cache  = new Psr16Cache(new FilesystemAdapter());
$client = new WeclappClient($config, cache: $cache);

// First call: fetches from API and caches for 5 minutes
$articles = $client->articles()->listAll();

// Second call within 5 minutes: served from cache, no HTTP request
$articles = $client->articles()->listAll();

// Invalidate after a write
$client->articles()->create([...]);
$client->articles()->clearCache(); // next listAll() will re-fetch
```

---

## PSR-3 Logging

Inject any PSR-3 logger (e.g. Monolog) to trace all HTTP requests and responses:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('weclapp');
$logger->pushHandler(new StreamHandler('weclapp.log'));

$client = new WeclappClient($config, logger: $logger);

// Logs at DEBUG level:
// [weclapp] GET customer/abc-123
// [weclapp] 200 GET customer/abc-123 (42ms)
```

---

## Memory-Efficient Streaming with `cursor()`

For very large datasets, use `cursor()` instead of `listAll()`.
It yields DTOs one by one using a PHP Generator — only one page is in memory at a time:

```php
foreach ($client->customers()->cursor() as $customer) {
    $mySystem->sync($customer);
    // Each customer is released from memory after this iteration
}

// With a filter:
$query = QueryBuilder::new()->filterEq('active', true)->sort('company');
foreach ($client->articles()->cursor($query) as $article) {
    echo $article->articleNumber . PHP_EOL;
}
```

> Use `cursor()` when processing tens of thousands of records to avoid memory exhaustion.
> For smaller datasets or when you need the full list upfront, `listAll()` is simpler.

---

## Status Enums

Use the provided enums to avoid magic string comparisons:

```php
use miralsoft\weclapp\api\Enum\SalesOrderStatus;
use miralsoft\weclapp\api\Enum\SalesInvoiceStatus;
use miralsoft\weclapp\api\Enum\SalesInvoiceType;
use miralsoft\weclapp\api\Enum\NumberRangeType;
use miralsoft\weclapp\api\Enum\ItemType;
use miralsoft\weclapp\api\Enum\InvoicingType;
use miralsoft\weclapp\api\Enum\QuotationStatus;
use miralsoft\weclapp\api\Enum\WebhookEntityName;

// Comparing order status
if (SalesOrderStatus::tryFrom($order->status) === SalesOrderStatus::Confirmed) {
    // process confirmed orders
}

// Filter by status using enum value
$confirmed = $client->salesOrders()->list(
    QueryBuilder::new()->filterEq('status', SalesOrderStatus::Confirmed->value)
);

// Use WebhookEntityName when registering webhooks
$client->webhooks()->register(
    entityName: WebhookEntityName::Party->value,
    url:        'https://my-app.example.com/webhooks/weclapp',
    atUpdate:   true,
);
```

### `SalesInvoiceStatus`

| Case | API value | Description |
|---|---|---|
| `New` | `NEW` | Created, not yet processed |
| `DocumentCreated` | `DOCUMENT_CREATED` | Invoice document has been generated |
| `OpenItemCreated` | `OPEN_ITEM_CREATED` | Posted to the accounts receivable open-item list |
| `EntryCompleted` | `ENTRY_COMPLETED` | Fully completed and booked |
| `Cancelled` | `CANCELLED` | Cancelled — a cancellation invoice was created |

### `SalesInvoiceType`

| Case | API value | Description |
|---|---|---|
| `StandardInvoice` | `STANDARD_INVOICE` | Regular invoice (RE-number range) |
| `CreditNote` | `CREDIT_NOTE` | **Cancellation invoice** (CLX-number range) |
| `AdvancePaymentInvoice` | `ADVANCE_PAYMENT_INVOICE` | Advance payment invoice |
| `FinalInvoice` | `FINAL_INVOICE` | Final invoice settling prior advance payments |
| `PartPaymentInvoice` | `PART_PAYMENT_INVOICE` | Partial payment invoice |
| `PrepaymentInvoice` | `PREPAYMENT_INVOICE` | Prepayment invoice |
| `RetailInvoice` | `RETAIL_INVOICE` | Retail / point-of-sale invoice |

```php
// Check if an invoice is a cancellation invoice
if (SalesInvoiceType::tryFrom($invoice->salesInvoiceType) === SalesInvoiceType::CreditNote) {
    echo 'Cancellation invoice: ' . $invoice->invoiceNumber;
}

// Shorthand via the DTO helper method
if ($invoice->isCreditNote()) {
    echo 'Cancellation invoice: ' . $invoice->invoiceNumber;
}

// Filter by item type on a sales order
foreach ($order->orderItems as $item) {
    if (ItemType::tryFrom($item->itemType) === ItemType::Service) {
        echo 'Service: ' . $item->title . ' (' . $item->invoicingType . ')' . PHP_EOL;
    }
}
```

### `ItemType`

Applies to `SalesOrderItemDTO::$itemType` and `SalesInvoiceItemDTO::$itemType`.

| Case | API value | Description |
|---|---|---|
| `Default` | `DEFAULT` | Standard article line item |
| `FreeText` | `FREE_TEXT` | Free-text position with no article reference |
| `Service` | `SERVICE` | Service item billed by effort or fixed price |
| `ServiceQuota` | `SERVICE_QUOTA` | Service item linked to a service quota |

### `InvoicingType`

Applies to `SalesOrderItemDTO::$invoicingType` (service items only).

| Case | API value | Description |
|---|---|---|
| `Effort` | `EFFORT` | Billed based on actual recorded effort (time tracking) |
| `FixedPrice` | `FIXED_PRICE` | Billed at a pre-agreed fixed price regardless of effort |

### `NumberRangeType`

All 45 entity types that have a configurable number series. Key values:

| Case | API value | Typical prefix |
|---|---|---|
| `SalesInvoice` | `SALES_INVOICE` | `RE-` |
| `SalesInvoiceCancellation` | `SALES_INVOICE_CANCELLATION` | `CLX-` |
| `ProformaInvoice` | `PROFORMA_INVOICE` | `PR-` *(configurable)* |
| `SalesOrder` | `SALES_ORDER` | `SO-` |
| `Quotation` | `QUOTATION` | `AN-` |
| `PurchaseOrder` | `PURCHASE_ORDER` | `BE-` |
| `PartyCustomer` | `PARTY_CUSTOMER` | `K-` |
| `Ticket` | `TICKET` | `TI-` |

```php
// Use with NumberRangeResource::findByType()
$range = $client->numberRanges()->findByType(NumberRangeType::ProformaInvoice);

// Or filter directly with the string value
$range = $client->numberRanges()->findByType('PROFORMA_INVOICE');
```

> All prefixes are tenant-configurable. Never hardcode `"PR-"` — always fetch via
> `$client->numberRanges()->getProformaInvoicePrefix()`.

---

## Webhook Signature Verification

Always verify the HMAC-SHA256 signature on incoming webhook requests to prevent spoofing:

```php
use miralsoft\weclapp\api\Util\WebhookValidator;

// In your webhook handler (e.g. a Symfony controller):
$secret    = $_ENV['WECLAPP_WEBHOOK_SECRET'];
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WECLAPP_SIGNATURE'] ?? '';

if (!WebhookValidator::verify($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Invalid webhook signature.');
}

$event = json_decode($payload, true);
// Process $event safely...
```

---

## Cache Management

After write operations you may want to invalidate the cache immediately:

```php
$client->articles()->create(['articleNumber' => 'ART-NEW', 'name' => 'New Product']);

// Invalidate so the next listAll() fetches fresh data
$client->articles()->clearCache();
```

---

## Testing

The test suite is split into two independent layers that can be run separately.

### Unit Tests (default — no credentials needed)

Unit tests use a Guzzle `MockHandler`. No network connection required, runs in ~1–2 s:

```bash
php vendor/bin/phpunit --testsuite Unit
# or just: php vendor/bin/phpunit
```

Inject a mock in your own code the same way:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock   = new MockHandler([
    new Response(200, [], json_encode([
        'result' => [['id' => 'abc', 'customerNumber' => 'K-1', ...]]
    ])),
]);
$guzzle = new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
$client = new WeclappClient($config, guzzle: $guzzle);

$result = $client->customers()->list(); // uses mocked response
```

### Integration Tests (live API — run on demand)

Integration tests call the real weclapp API and verify that responses are structured
as expected.

**Setup:**

```bash
cp tests/.env.test.example tests/.env.test
# Edit tests/.env.test and fill in your tenant + token
```

```ini
# tests/.env.test (required)
WECLAPP_TENANT=your-tenant   # subdomain of your weclapp URL
WECLAPP_TOKEN=your-api-token

# Optional: named test data anchors (makes create-dry-run tests more deterministic)
# WECLAPP_TEST_CUSTOMER_NUMBER=K-10042    # resolved once via findByCustomerNumber()
# WECLAPP_TEST_SALES_ORDER_NUMBER=SO-123  # resolved once via findByOrderNumber()
```

When `WECLAPP_TEST_CUSTOMER_NUMBER` / `WECLAPP_TEST_SALES_ORDER_NUMBER` are set,
all integration tests that need a customer ID or order ID use these anchors instead
of falling back to the first entry from a `list()` call — more deterministic and
independent of list ordering changes.

**Run (read-only + dry-run — safe against any tenant):**

```bash
php vendor/bin/phpunit --testsuite Integration
```

If `tests/.env.test` is missing or credentials are empty, all integration tests are
automatically **skipped** (not failed) — so running the full suite without credentials is safe:

```bash
php vendor/bin/phpunit   # Unit: OK · Integration: S (skipped)
```

**What is tested:**

| Test class | Nature | Checks |
|---|---|---|
| `CustomerResourceIntegrationTest` | read-only | list, find by ID, count, `modifiedSince`, `findByCustomerNumber`, NotFoundException |
| `SalesOrderResourceIntegrationTest` | read-only | list, find by ID, status enum, `findByOrderNumber`, `findByCustomer`, `findByStatus` |
| `SalesInvoiceResourceIntegrationTest` | read-only | list, find by ID, status enum, count, `findByInvoiceNumber`, `findCreditNotes`, `resolveCustomerDisplayName` |
| `ArticleResourceIntegrationTest` | read-only | list, find by number, NotFoundException, `findCategoryIdByNumber`, `cursor()` lazy pagination |
| `ContactResourceIntegrationTest` | read-only | list, find by ID, count, `loadFromStubs()` |
| `NumberRangeResourceIntegrationTest` | read-only | at least one range, all types known, proforma prefix, `isCurrentlyActive()` |
| `QuantityUnitResourceTest` | **unit test** | DTO hydration, `isTimeUnit()`, `getMilliseconds()`, `findTimeUnits()` filtering, `findByName()`, CRUD, fail-soft guard |
| `CustomAttributeDefinitionResourceTest` | **unit test** | DTO hydration, `appliesTo()`, `findByEntity()`/`findByKey()` client-side filter, `ensure()` idempotency (reuse + create) |
| `CustomAttributeDTOTest` | **unit test** | `value()` reader, payload builders (`string`/`number`/`boolean`/`date` ms-precise/`selection`) |
| `CustomAttributeValueIntegrationTest` | **unit test** | `setCustomAttribute()` Read-Modify-Write (append/replace), DTO `getCustomAttribute()`/`getCustomAttributeValue()` |
| `WebhookResourceIntegrationTest` | read-only | list, count, required fields, `findByUrl`, `findByEntityName` |
| `DryRunIntegrationTest` | **dry-run** | Customer, SalesOrder, Quotation, Article, SalesInvoice, Supplier, Contact — payloads validated; nothing persisted |

### Write Tests (live API — opt-in only)

Write tests create, update and delete **real records** in the tenant.
They are gated behind `WECLAPP_ALLOW_WRITES=true` and skipped if that variable is not set.

**Run:**

```bash
# In tests/.env.test, add:
WECLAPP_ALLOW_WRITES=true

# Or inline:
WECLAPP_ALLOW_WRITES=true php vendor/bin/phpunit --testsuite Write
```

The `Write` suite is a named alias for the write test files in `phpunit.xml`.
All write tests clean up after themselves (try/finally) so the tenant remains pristine.

| Test class | Creates / Deletes |
|---|---|
| `WebhookWriteIntegrationTest` | Webhook records — full Create → Update → Delete lifecycle, `ensureSubscription` idempotency, `deactivate`, `reactivate` (live-verified), `findByUrl` after creation |
| `ArticleWriteIntegrationTest` | No new records — mutates and restores an existing article. Verifies `patch()` round-trip, `findRaw()` backup/restore round-trip, and dry-run patch. |

---

## DTO Reference

All API responses are returned as typed, immutable DTOs.

### Common fields (all DTOs)

| Field | Type | Description |
|---|---|---|
| `id` | `string` | Internal weclapp UUID |
| `version` | `string` | Optimistic locking version |
| `createdDate` | `int` | Creation timestamp (epoch ms) |
| `lastModifiedDate` | `int` | Last modification timestamp (epoch ms) |
| `getCreatedAt()` | `?DateTimeImmutable` | Creation date as object |
| `getLastModifiedAt()` | `?DateTimeImmutable` | Last modification date as object |
| `toArray()` | `array` | Serialise back to associative array |

### CustomerDTO / ContactDTO / SupplierDTO / PartyDTO

All four DTOs map the **137-field** weclapp `party` schema (29 common `abstractParty` fields + 108
party-specific fields). Each corresponds to a different endpoint:

| DTO | Endpoint | Key identifier field |
|---|---|---|
| `CustomerDTO` | `/api/v2/customer` | `$customerNumber` |
| `ContactDTO` | `/api/v2/contact` | `$parentPartyId` (parent company) |
| `SupplierDTO` | `/api/v2/supplier` | `$supplierNumber` |
| `PartyDTO` | `/api/v2/party` | `$customerNumber` or `$supplierNumber` |

**Common / identity fields (all 4 DTOs):**

| Field | Type | Description |
|---|---|---|
| `id`, `version` | `string` | UUID and optimistic locking version |
| `createdDate`, `lastModifiedDate` | `int` | Timestamps (epoch ms) |
| `partyType` | `?string` | `ORGANIZATION` or `PERSON` |
| `salutation` | `?string` | Salutation enum |
| `company`, `company2` | `?string` | Company name lines |
| `firstName`, `lastName`, `middleName` | `?string` | Person name fields |
| `birthDate` | `?int` | Date of birth (epoch ms) |
| `titleId` | `?string` | Academic title ID |
| `email`, `emailHome` | `?string` | E-mail addresses |
| `phone`, `phoneHome` | `?string` | Phone numbers |
| `mobilePhone1`, `mobilePhone2` | `?string` | Mobile phone numbers |
| `fax`, `fixPhone2` | `?string` | Fax / secondary fixed line |
| `website` | `?string` | Website URL |
| `personCompany`, `personDepartmentId`, `personRoleId` | `?string` | Person-specific org fields |
| `imageId` | `?string` | Profile image ID |
| `description` | `?string` | Internal notes |
| `parentPartyId` | `?string` | Parent party / company ID |
| `primaryContactId` | `?string` | Primary contact ID |
| `primaryAddressId`, `deliveryAddressId`, `invoiceAddressId`, `dunningAddressId` | `?string` | Address IDs |
| `currencyId`, `commercialLanguageId` | `?string` | Currency / language |
| `responsibleUserId` | `?string` | Responsible user ID |
| `fixedResponsibleUser` | `bool` | Fixed responsible user flag |
| `taxId`, `vatIdentificationNumber`, `xRechnungLeitwegId`, `eoriNumber` | `?string` | Tax / customs IDs |
| `factoring`, `commissionBlock`, `invoiceBlock` | `bool` | Block flags |
| `optInEmail`, `optInLetter`, `optInPhone`, `optInSms` | `bool` | Marketing opt-ins |
| `salesPartner`, `competitor`, `habitualExporter`, `formerSalesPartner` | `bool` | Party roles |
| `salesPartnerDefaultCommissionFix`, `salesPartnerDefaultCommissionPercentage` | `?string` | Commission defaults (decimal) |
| `salesPartnerDefaultCommissionType` | `?string` | Commission type enum |
| `referenceNumber`, `regionId`, `sectorId`, `companySizeId`, `legalFormId` | `?string` | Classification IDs |
| `ratingId`, `leadRatingId`, `leadSourceId`, `leadStatus` | `?string` | Lead management |
| `convertedOnDate` | `?int` | Lead conversion date (epoch ms) |
| `invoiceRecipientId` | `?string` | Invoice recipient ID |
| `deliveryEmailAddressesId`, `dunningEmailAddressesId`, `purchaseEmailAddressesId` | `?string` | E-mail group IDs |
| `quotationEmailAddressesId`, `salesInvoiceEmailAddressesId`, `salesOrderEmailAddressesId` | `?string` | E-mail group IDs |
| `purchaseViaPlafond`, `enableDropshippingInNewSupplySources` | `bool` | Purchase flags |
| `publicPageUuid` | `?string` | Public page UUID |
| `publicPageExpirationDate` | `?int` | Public page expiry (epoch ms) |

**Customer-specific fields (`customer = true`):**

| Field | Type | Description |
|---|---|---|
| `customer` | `bool` | Customer role flag |
| `customerNumber`, `customerNumberOld` | `?string` | Customer numbers |
| `customerBlocked`, `customerDeliveryBlock`, `customerInsolvent`, `customerInsured` | `bool` | Customer block/status flags |
| `customerUseCustomsTariffNumber`, `customerAllowDropshippingOrderCreation` | `bool` | Customer options |
| `customerBusinessType` | `?string` | Business type enum |
| `customerCategoryId` | `?string` | Category ID |
| `customerCreditLimit`, `customerAmountInsured`, `customerAnnualRevenue` | `?string` | Amounts (decimal strings) |
| `customerDefaultHeaderDiscount`, `customerDefaultHeaderSurcharge` | `?string` | Default discounts (decimal) |
| `customerBlockNotice` | `?string` | Block notice text |
| `customerCurrentSalesStageId`, `customerLossReasonId` | `?string` | CRM stage IDs |
| `customerLossDescription`, `customerInternalNote` | `?string` | Notes |
| `customerDebtorAccountId`, `customerDebtorAccountingCodeId` | `?string` | Accounting IDs |
| `customerDefaultShippingCarrierId`, `customerDefaultWarehouseId` | `?string` | Logistics defaults |
| `customerNonStandardTaxId` | `?string` | Non-standard tax ID |
| `customerPaymentMethodId`, `customerTermOfPaymentId`, `customerShipmentMethodId` | `?string` | Payment / shipping |
| `customerSalesChannel` | `?string` | Sales channel (distributionChannel enum) |
| `customerSalesOrderPaymentType` | `?string` | Default payment type enum |
| `customerSalesProbability` | `?int` | Sales probability 0–100 |
| `customerSatisfaction` | `?string` | Satisfaction level enum |
| `customerSupplierNumber` | `?string` | Customer's number in supplier systems |
| `customerSalesStageHistory` | `array` | Sales stage history (raw) |

**Supplier-specific fields (`supplier = true`):**

| Field | Type | Description |
|---|---|---|
| `supplier` | `bool` | Supplier role flag |
| `supplierActive` | `bool` | Supplier account active flag |
| `supplierOrderBlock` | `bool` | Order block flag |
| `supplierMergeItemsForOcrInvoiceUpload` | `bool` | OCR invoice merge flag |
| `supplierNumber`, `supplierNumberOld` | `?string` | Supplier numbers |
| `supplierCreditorAccountId`, `supplierCreditorAccountingCodeId` | `?string` | Accounting IDs |
| `supplierCustomerNumberAtSupplier` | `?string` | Our number at this supplier |
| `supplierDefaultShippingCarrierId`, `supplierShipmentMethodId` | `?string` | Logistics defaults |
| `supplierPaymentMethodId`, `supplierTermOfPaymentId` | `?string` | Payment terms |
| `supplierMinimumPurchaseOrderAmount` | `?string` | Minimum order amount (decimal) |
| `supplierNonStandardTaxId` | `?string` | Non-standard tax ID |
| `supplierInternalNote` | `?string` | Internal note |

**Typed nested arrays:**

| Field | Type | Description |
|---|---|---|
| `addresses` | `list<AddressDTO>` | Party addresses |
| `bankAccounts` | `list<BankAccountDTO>` | Bank accounts (29 fields each) |
| `onlineAccounts` | `list<OnlineAccountDTO>` | Online accounts / social profiles |
| `commissionSalesPartners` | `list<CommissionSalesPartnerDTO>` | Sales partner commissions |
| `partyHabitualExporterLettersOfIntent` | `list<PartyHabitualExporterLetterOfIntentDTO>` | Habitual exporter letters |
| `customAttributes` | `list<CustomAttributeDTO>` | Custom attribute values |
| `contacts`, `partyEmailAddresses`, `tags`, `topics` | `list<array>` | Raw arrays (no DTO schema) |

**Helper methods (all 4 DTOs):**

| Method | Returns | Description |
|---|---|---|
| `getDisplayName()` | `string` | Company name or "First Last" |
| `getCreatedAt()` | `?DateTimeImmutable` | Creation date |
| `getLastModifiedAt()` | `?DateTimeImmutable` | Last modification date |
| `getBirthDate()` | `?DateTimeImmutable` | Date of birth (CustomerDTO/ContactDTO) |
| `getCustomerCreditLimit()` | `?float` | Credit limit as float (CustomerDTO/PartyDTO) |
| `isCustomer()` / `isSupplier()` | `bool` | Role check (PartyDTO) |
| `isBlocked()` | `bool` | `$customerBlocked` shorthand (CustomerDTO) |
| `isActive()` | `bool` | `$supplierActive && !$supplierOrderBlock` (SupplierDTO) |
| `getFullName()` | `string` | "First Last" (ContactDTO) |

> **Breaking changes from previous versions:** `$mobile` → `$mobilePhone1`; `$blocked` → `$customerBlocked`
> (CustomerDTO) or `$supplierOrderBlock` (SupplierDTO); `$active` → `$supplierActive` (SupplierDTO) or removed (CustomerDTO);
> `$insolvent` → `$customerInsolvent`; `$vatRegistrationNumber` → `$vatIdentificationNumber`/`$taxId`;
> `$paymentTermId` → `$customerTermOfPaymentId`/`$supplierTermOfPaymentId`; `$salesChannel` → `$customerSalesChannel`;
> `$customerId` (ContactDTO) → `$parentPartyId`.

### ArticleDTO

All 94 fields of the weclapp `article` schema are mapped. Key fields:

| Field | Type | Description |
|---|---|---|
| `articleNumber` | `string` | Unique SKU / article number |
| `name` | `string` | Short article name |
| `description` | `?string` | Short HTML description |
| `longText` | `?string` | Long HTML description (API key: `longText`) |
| `shortDescription1`, `shortDescription2` | `?string` | Short description lines |
| `internalNote` | `?string` | Internal HTML note |
| `matchCode` | `?string` | Alternative search code |
| `articleType` | `?string` | Article type (enum) |
| `barcode`, `ean`, `catalogCode` | `?string` | Identification codes |
| `active` | `bool` | Whether the article is active |
| `availableInSale` | `bool` | Whether the article can be sold (API key: `availableInSale`) |
| `serialNumberRequired`, `batchNumberRequired` | `bool` | Tracking requirements |
| `productionArticle` | `bool` | Whether the article is manufactured in-house |
| `unitId` | `?string` | Base unit of measure ID (resolve via `/unit/{id}`) |
| `articleCategoryId` | `?string` | Assigned category ID |
| `taxRateType`, `invoicingType` | `?string` | Tax and invoicing type enums |
| `manufacturerId`, `manufacturerPartNumber` | `?string` | Manufacturer data |
| `customsTariffNumberId`, `countryOfOriginCode` | `?string` | Customs data |
| `minimumStockQuantity`, `targetStockQuantity` | `?string` | Stock thresholds (decimal strings) |
| `minimumPurchaseQuantity`, `fixedPurchaseQuantity` | `?string` | Purchase quantities (decimal strings) |
| `articleGrossWeight`, `articleNetWeight` | `?string` | Weight (decimal strings) |
| `articleLength`, `articleWidth`, `articleHeight` | `?string` | Dimensions (decimal strings) |
| `averageDeliveryTime`, `procurementLeadDays` | `int` | Lead times in days |
| `packagingQuantity` | `int` | Packaging unit size |
| `launchDate`, `sellFromDate`, `sellByDate`, `supportUntilDate` | `?int` | Lifecycle dates (epoch ms) |
| `articleImages` | `list<ArticleImageDTO>` | Article images |
| `articlePrices` | `list<ArticlePriceDTO>` | Sales price entries (customer/channel/scale-specific) |
| `articleCalculationPrices` | `list<ArticleCalculationPriceDTO>` | Calculation/purchase price entries |
| `articleAlternativeQuantities` | `list<ArticleAlternativeQuantityDTO>` | Warehouse-specific quantity configs |
| `customerArticleNumbers` | `list<CustomerSpecificArticleAttributesDTO>` | Customer-specific article numbers |
| `quantityConversions` | `list<QuantityConversionDTO>` | Unit-of-measure conversions |
| `supplySources` | `list<SupplySourceDTO>` | Procurement supply sources |
| `productionBillOfMaterialItems` | `list<BillOfMaterialItemDTO>` | Production BOM components |
| `salesBillOfMaterialItems` | `list<BillOfMaterialItemDTO>` | Sales BOM components |
| `customAttributes` | `list<CustomAttributeDTO>` | Custom attribute values |
| `getMainImage()` | `?ArticleImageDTO` | Returns the main image, or first image |
| `isBillOfMaterial()` | `bool` | `true` if the article has BOM components |

> Note: `salesPrice`, `purchasePrice`, `availableStock`, `reservedStock` are **not** part of the
> weclapp OpenAPI `article` schema. Prices are accessed via `articlePrices`; stock levels
> are available via separate stock endpoints.

### ArticleCategoryDTO

| Field | Type | Description |
|---|---|---|
| `name` | `string` | Category display name |
| `description` | `?string` | Optional description |
| `parentCategoryId` | `?string` | Parent category ID (null for root categories) |
| `imageId` | `?string` | Category image ID (readOnly) |
| `articleAccountingCodeId` | `?string` | Default accounting code for articles |
| `articleCategoryClassificationId` | `?string` | Classification ID |
| `costTypeId` | `?string` | Default cost type ID |
| `salesCostCenterId`, `purchaseCostCenterId` | `?string` | Default cost centre IDs |
| `isRootCategory()` | `bool` | `true` if `parentCategoryId === null` |

### SalesOrderDTO

| Field | Type | Description |
|---|---|---|
| `orderNumber` | `string` | Human-readable order number (e.g. `SO-10042`) |
| `status` | `string` | Order status — see `SalesOrderStatus` enum |
| `customerId` | `string` | ID of the linked customer |
| `orderNumberAtCustomer` | `?string` | Customer's own reference / order number |
| `orderDate` | `int` | Order date (epoch ms) |
| `deliveryDate` | `?int` | Requested delivery date (epoch ms) |
| `shippingDate` | `?int` | Actual shipping date (epoch ms) |
| `netAmount`, `grossAmount` | `?string` | Net / gross order amount (decimal strings) |
| `recordCurrencyId` | `?string` | Document currency ID (resolve via `/currency/{id}`) |
| `salesChannel` | `?string` | Assigned sales channel |
| `responsibleUserId` | `?string` | ID of the responsible weclapp user |
| `deliveryAddress`, `invoiceAddress`, `recordAddress` | `?RecordAddressDTO` | Embedded addresses |
| `orderItems` | `list<SalesOrderItemDTO>` | Typed line items — see `SalesOrderItemDTO` |
| `tags`, `customAttributes` | `array` | Tag and custom attribute objects |
| `getOrderDate()` | `?DateTimeImmutable` | Order date as object |
| `getDeliveryDate()` | `?DateTimeImmutable` | Delivery date as object |
| `getShippingDate()` | `?DateTimeImmutable` | Shipping date as object |
| `getNetAmount()` | `?float` | Net amount as float |
| `isFullyFulfilled()` | `bool` | `true` when invoiced, shipped and paid |

### SalesOrderItemDTO

Embedded in `SalesOrderDTO::$orderItems`. Maps the `salesOrderItem` schema.

| Field | Type | Description |
|---|---|---|
| `articleId` | `?string` | ID of the linked article (null for FREE\_TEXT items) |
| `title` | `?string` | Line item title / article name |
| `description` | `?string` | HTML description |
| `quantity` | `?string` | Ordered quantity (decimal string) |
| `unitId` | `?string` | ID of the unit of measure |
| `unitPrice` | `?string` | Net unit price (decimal string) |
| `unitCost` | `?string` | Purchase/cost price per unit (decimal string) |
| `discountPercentage` | `?string` | Discount percentage (decimal string) |
| `grossAmount` | `?string` | Total gross amount (readOnly, decimal string) |
| `netAmount` | `?string` | Total net amount (readOnly, decimal string) |
| `netAmountInCompanyCurrency` | `?string` | Net amount in company currency (readOnly) |
| `grossAmountInCompanyCurrency` | `?string` | Gross amount in company currency (readOnly) |
| `unitPriceInCompanyCurrency` | `?string` | Unit price in company currency (readOnly) |
| `unitCostInCompanyCurrency` | `?string` | Unit cost in company currency (readOnly) |
| `netAmountForStatistics` | `?string` | Net amount for statistics (readOnly) |
| `recommendedRetailPrice` | `?string` | Recommended retail price (readOnly) |
| `invoicedQuantity` | `?string` | Already invoiced quantity (readOnly) |
| `shippedQuantity` | `?string` | Already shipped quantity (readOnly) |
| `returnedQuantity` | `?string` | Returned quantity (readOnly) |
| `shipped` | `bool` | `true` if fully shipped (readOnly) |
| `positionNumber` | `int` | Display position (1-based) |
| `itemType` | `?string` | Item type — see `ItemType` enum |
| `invoicingType` | `?string` | Invoicing mode — see `InvoicingType` enum |
| `note` | `?string` | Internal staff note |
| `groupName` | `?string` | Group header |
| `parentItemId` | `?string` | Parent item ID (sub-positions) |
| `addPageBreakBefore` | `bool` | Insert page break before in PDF |
| `taxId` | `?string` | ID of the applied tax rate |
| `manualQuantity` | `bool` | Quantity entered manually |
| `manualUnitPrice` | `bool` | Unit price entered manually |
| `manualUnitCost` | `bool` | Unit cost entered manually |
| `manualPlannedWorkingTimePerUnit` | `bool` | Working time entered manually |
| `servicePeriodFrom` / `servicePeriodTo` | `?int` | Service period (epoch ms) |
| `plannedDeliveryDate` / `plannedShippingDate` | `?int` | Planning dates (epoch ms) |
| `plannedWorkingTimePerUnit` | `?int` | Planned working time in minutes |
| `contractChargeId` | `?string` | Related contract charge (readOnly) |
| `serviceQuotaId` | `?string` | Related service quota (readOnly) |
| `commissionSalesPartners`, `picks`, `tasks` | `array` | Nested relation arrays |
| `ecommerceOrderItemIds`, `reductionAdditionItems`, `customAttributes` | `array` | Nested arrays |
| `getQuantity()` | `?float` | Quantity as float |
| `getUnitPrice()` | `?float` | Unit price as float |
| `getNetAmount()` | `?float` | Net amount as float |
| `getGrossAmount()` | `?float` | Gross amount as float |
| `isFreeText()` | `bool` | `true` if item has no article reference |
| `isService()` | `bool` | `true` if `itemType` is `SERVICE` or `SERVICE_QUOTA` |

### SalesInvoiceDTO

| Field | Type | Description |
|---|---|---|
| `invoiceNumber` | `string` | Human-readable invoice number (e.g. `RE-10042`, `CLX-1061`, `PR-0042`) |
| `status` | `string` | Invoice status — see `SalesInvoiceStatus` enum |
| `salesInvoiceType` | `string` | Invoice type — see `SalesInvoiceType` enum. `CREDIT_NOTE` = cancellation invoice |
| `customerId` | `string` | ID of the linked customer |
| `invoiceDate` | `int` | Invoice date (epoch ms) |
| `dueDate` | `?int` | Payment due date (epoch ms) |
| `bookingDate` | `?int` | Accounting booking date (epoch ms) |
| `paymentMethodId` | `?string` | ID of the assigned payment method |
| `paymentStatus` | `?string` | e.g. `OPEN`, `PAID`, `CLEARED_WITH_CREDIT_NOTE` |
| `paid` | `bool` | `true` if the invoice has been fully paid |
| `netAmount`, `grossAmount` | `?string` | Net / gross invoice amount (decimal strings) |
| `netAmountInCompanyCurrency`, `grossAmountInCompanyCurrency` | `?string` | Amounts in company currency |
| `recordCurrencyId` | `?string` | Document currency ID (resolve via `/currency/{id}`) |
| `salesOrderId` | `?string` | ID of the originating sales order (if any) |
| `precedingSalesInvoiceId` | `?string` | For `CREDIT_NOTE`: UUID of the original cancelled invoice |
| `cancellationNumber` | `?string` | For cancelled invoices: CLX-number of the associated credit note |
| `orderNumberAtCustomer` | `?string` | Customer's own reference number |
| `deliveryAddress`, `recordAddress` | `?RecordAddressDTO` | Embedded addresses |
| `salesInvoiceItems` | `list<SalesInvoiceItemDTO>` | Typed line items — see `SalesInvoiceItemDTO` |
| `recordEmailAddresses` | `?EmailAddressesDTO` | E-mail address overrides |
| `tags`, `customAttributes` | `array` | Tag and custom attribute objects |
| `isCreditNote()` | `bool` | `true` if `salesInvoiceType === 'CREDIT_NOTE'` |
| `getNetAmount()` | `?float` | Net amount as float |
| `getGrossAmount()` | `?float` | Gross amount as float |
| `getInvoiceDate()` | `?DateTimeImmutable` | Invoice date as object |
| `getDueDate()` | `?DateTimeImmutable` | Due date as object |
| `getBookingDate()` | `?DateTimeImmutable` | Booking date as object |

> **Proforma invoices** are **not** identified by `salesInvoiceType` — that field never
> contains a proforma-specific value. Use `$client->numberRanges()->getProformaInvoicePrefix()`
> to fetch the tenant-configured prefix and check `$invoice->invoiceNumber` against it.

### SalesInvoiceItemDTO

Embedded in `SalesInvoiceDTO::$salesInvoiceItems`. Maps the `salesInvoiceItem` schema.

| Field | Type | Description |
|---|---|---|
| `articleId` | `?string` | ID of the linked article (null for FREE\_TEXT items) |
| `title` | `?string` | Line item title |
| `description` | `?string` | HTML description |
| `quantity` | `?string` | Invoiced quantity (decimal string) |
| `unitId` | `?string` | ID of the unit of measure |
| `unitPrice` | `?string` | Net unit price (decimal string) |
| `unitCost` | `?string` | Purchase/cost price per unit (decimal string) |
| `discountPercentage` | `?string` | Discount percentage (decimal string) |
| `grossAmount` | `?string` | Total gross amount (readOnly, decimal string) |
| `netAmount` | `?string` | Total net amount (readOnly, decimal string) |
| `netAmountInCompanyCurrency` | `?string` | Net amount in company currency (readOnly) |
| `grossAmountInCompanyCurrency` | `?string` | Gross amount in company currency (readOnly) |
| `unitPriceInCompanyCurrency` | `?string` | Unit price in company currency (readOnly) |
| `unitCostInCompanyCurrency` | `?string` | Unit cost in company currency (readOnly) |
| `netAmountForStatistics` | `?string` | Net amount for statistics (readOnly) |
| `recommendedRetailPrice` | `?string` | Recommended retail price (readOnly) |
| `positionNumber` | `int` | Display position (1-based) |
| `itemType` | `?string` | Item type — see `ItemType` enum |
| `note` | `?string` | Internal staff note |
| `groupName` | `?string` | Group header |
| `parentItemId` | `?string` | Parent item ID (sub-positions) |
| `addPageBreakBefore` | `bool` | Insert page break before in PDF |
| `taxId` | `?string` | ID of the applied tax rate |
| `accountId` | `?string` | Revenue account ID (accounting integration) |
| `costTypeId` | `?string` | Cost type ID (cost accounting) |
| `cost2CostCenterId` | `?string` | Secondary cost centre ID |
| `creditedInvoiceItemId` | `?string` | For credit note items: ID of the original invoice item being cancelled |
| `contractItemId` | `?string` | Originating contract item ID (readOnly) |
| `manualQuantity` | `bool` | Quantity entered manually |
| `manualUnitPrice` | `bool` | Unit price entered manually |
| `manualUnitCost` | `bool` | Unit cost entered manually |
| `servicePeriodFrom` / `servicePeriodTo` | `?int` | Service period (epoch ms) |
| `deliveryDate` / `shippingDate` | `?int` | Delivery / shipping date for this line (epoch ms) |
| `commissionSalesPartners`, `costCenterItems` | `array` | Nested relation arrays |
| `reductionAdditionItems`, `salesInvoiceItemRelationships` | `array` | Nested arrays |
| `serialNumbers`, `customAttributes` | `array` | Nested arrays |
| `getQuantity()` | `?float` | Quantity as float |
| `getUnitPrice()` | `?float` | Unit price as float |
| `getNetAmount()` | `?float` | Net amount as float |
| `getGrossAmount()` | `?float` | Gross amount as float |
| `isFreeText()` | `bool` | `true` if item has no article reference |
| `isCreditNoteItem()` | `bool` | `true` if `creditedInvoiceItemId` is set |

### QuotationDTO

| Field | Type | Description |
|---|---|---|
| `quotationNumber` | `string` | Human-readable quotation number |
| `status` | `string` | Quotation status — see `QuotationStatus` enum |
| `customerId` | `string` | ID of the linked customer |
| `netAmount`, `grossAmount` | `?string` | Net / gross quotation amount (decimal strings) |
| `recordCurrencyId` | `?string` | Document currency ID (resolve via `/currency/{id}`) |
| `deliveryAddress`, `invoiceAddress`, `recordAddress` | `?RecordAddressDTO` | Embedded addresses |
| `quotationItems` | `array` | Raw line item arrays |
| `getNetAmount()` | `?float` | Net amount as float |
| `isExpired()` | `bool` | `true` if the quotation validity date has passed |
| `getValidUntil()` | `?DateTimeImmutable` | Validity date as object |

---

## Migration from v1

Version 1 classes (`Customer`, `Article`, `SalesOrder`, etc.) are still present but
marked `@deprecated`. They continue to work against the v1 API endpoint until you migrate.

```php
// ❌ Old (v1, deprecated — API shuts down August 2025)
use miralsoft\weclapp\api\Config;
use miralsoft\weclapp\api\Customer;

Config::$URI   = 'https://miralsoft.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'your-token';

$customer = new Customer();
$list     = $customer->get(1, 50, 'customerNumber');

// ✅ New (v2)
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\Query\QueryBuilder;

$config = new WeclappConfig(tenant: 'miralsoft', token: 'your-token');
$client = new WeclappClient($config);

$result = $client->customers()->list(
    QueryBuilder::new()->page(1)->pageSize(50)->sort('customerNumber')
);
```

---

## Running the Tests

```bash
composer install
vendor/bin/phpunit
```

---

## License

Proprietary — © miralsoft, Michael Tosch
