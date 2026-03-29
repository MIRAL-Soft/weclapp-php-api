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
| `$client->parties()` | `/party` | Party identity lookup (resolves partyId to name/number) |
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

// Display name (company name for ORGANIZATION, "First Last" for PERSON)
echo $customer->getDisplayName();
```

### Contacts

```php
$contacts = $client->contacts();

// Find all contacts belonging to a customer
$contacts = $contacts->findByCustomer($customerId);

// Find by email
$contacts = $contacts->findByEmail('max@acme.de');

echo $contact->getFullName(); // "Max Mustermann"
```

### Articles

```php
$articles = $client->articles();

// Find by article number (SKU)
$article = $articles->findByArticleNumber('ART-001');

// Find all articles in a category
$articles = $articles->findByCategory($categoryId);

// Find only in-stock articles
$inStock  = $articles->findInStock();

echo $article->isInStock() ? 'In stock' : 'Out of stock';
echo $article->availableStock;
echo $article->salesPrice;
```

### Article Categories

```php
$categories = $client->articleCategories();

// Find by name (exact match)
$category = $categories->findByName('Electronics');

// Find only root categories (no parent)
$roots = $categories->findRootCategories();

echo $category->isRootCategory() ? 'Root' : 'Sub-category of: ' . $category->parentCategoryName;
```

### Sales Orders

```php
$orders = $client->salesOrders();

// Find all orders for a customer
$orders = $orders->findByCustomer($customerId);

// Find by status
$open = $orders->findByStatus('ORDER_CONFIRMED');

// Download order confirmation PDF
$pdf = $orders->getPdf($orderId);
file_put_contents('order-confirmation.pdf', $pdf);

// Date helpers
echo $order->getOrderDate()?->format('d.m.Y');
echo $order->getDeliveryDate()?->format('d.m.Y');
```

### Sales Invoices

```php
$invoices = $client->salesInvoices();

// Find open (unpaid) invoices
$open = $invoices->findOpen();
echo $invoice->openAmount;
echo $invoice->isOpen() ? 'Unpaid' : 'Paid';

// Download invoice PDF
$pdf = $invoices->getPdf($invoiceId);
file_put_contents('invoice.pdf', $pdf);

// Resolve the customer display name correctly for ORGANIZATION and PERSON types.
// The weclapp API does not return a top-level customerName field on invoices,
// so a party/id/{partyId} lookup is performed automatically when needed.
// Results are cached in memory — multiple invoices for the same customer
// produce only one additional API call.
foreach ($invoices->findOpen() as $invoice) {
    $name = $invoices->resolveCustomerDisplayName($invoice);
    // → "Acme Ltd." for an organisation, "John Smith" for a private customer
    echo $invoice->invoiceNumber . ' — ' . $name . PHP_EOL;
}

// Quick inline fallback (no extra API call — uses only inline invoice data)
echo $invoice->getCustomerDisplayName();
// → customerName (if returned) → customerNumber → 'Unknown'
```

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
$webhooks = $client->webhooks();

// Register a new webhook
$webhook = $webhooks->register(
    eventType:   'party.updated',                          // triggers on any customer/contact/supplier change
    callbackUrl: 'https://my-app.example.com/weclapp',    // must be HTTPS and publicly reachable
    description: 'Sync customer changes to ticket system'
);

// Available event types:
// article.created / article.updated / article.deleted
// salesOrder.created / salesOrder.updated / salesOrder.deleted
// salesInvoice.created / salesInvoice.updated
// quotation.created / quotation.updated
// party.created / party.updated / party.deleted

// List all registered webhooks
$all = $webhooks->all();

// Remove a webhook
$webhooks->delete($webhook->id);
```

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
use miralsoft\weclapp\api\Enum\QuotationStatus;
use miralsoft\weclapp\api\Enum\WebhookEventType;

// Comparing order status
if (SalesOrderStatus::tryFrom($order->status) === SalesOrderStatus::Confirmed) {
    // process confirmed orders
}

// Filter by status using enum value
$confirmed = $client->salesOrders()->list(
    QueryBuilder::new()->filterEq('status', SalesOrderStatus::Confirmed->value)
);

// Use enum when registering webhooks
$client->webhooks()->register(
    eventType:   WebhookEventType::PartyUpdated->value,
    callbackUrl: 'https://my-app.example.com/webhooks/weclapp',
);
```

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

## Testing / Custom HTTP Client

Inject a Guzzle `MockHandler` for unit tests — no real API calls required:

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

### CustomerDTO

| Field | Type |
|---|---|
| `customerNumber` | `string` |
| `company` | `string` |
| `partyType` | `string` (`ORGANIZATION` / `PERSON`) |
| `firstName`, `lastName` | `?string` |
| `email`, `phone`, `mobile` | `?string` |
| `active`, `blocked`, `insolvent` | `bool` |
| `currencyName`, `salesChannel` | `?string` |
| `addresses`, `contacts`, `customAttributes` | `array` |
| `getDisplayName()` | `string` |

### ArticleDTO

| Field | Type |
|---|---|
| `articleNumber` | `string` |
| `name` | `string` |
| `salesPrice`, `purchasePrice` | `?float` |
| `availableStock`, `reservedStock` | `?float` |
| `active`, `sellable`, `stockable` | `bool` |
| `articleCategoryId`, `unit` | `?string` |
| `isInStock()` | `bool` |

### SalesOrderDTO / SalesInvoiceDTO / QuotationDTO

| Field | Type |
|---|---|
| `orderNumber` / `invoiceNumber` / `quotationNumber` | `string` |
| `status` | `string` |
| `customerId` | `string` |
| `netAmount`, `grossAmount` | `?float` |
| `currency` | `?string` |
| `orderItems` / `invoiceItems` / `quotationItems` | `array` |

#### Additional fields on `SalesInvoiceDTO`

| Field | Type | Description |
|---|---|---|
| `partyId` | `?string` | ID of the underlying party record — use with `$client->parties()->find()` |
| `customerNumber` | `?string` | Human-readable customer number (e.g. `K-10042`) |
| `customerName` | `?string` | Denormalised display name — not always returned by the API |
| `getCustomerDisplayName()` | `string` | Best available inline name: `customerName` → `customerNumber` → `'Unknown'` |

> To get a fully resolved display name that correctly handles ORGANIZATION vs. PERSON,
> use `SalesInvoiceResource::resolveCustomerDisplayName($invoice)` instead.

### PartyDTO

| Field | Type | Description |
|---|---|---|
| `id` | `string` | Internal weclapp UUID |
| `partyType` | `string` | `ORGANIZATION` or `PERSON` |
| `customerNumber` | `?string` | Human-readable customer number |
| `company` | `?string` | Company name (ORGANIZATION) |
| `firstName`, `lastName` | `?string` | Person name fields (PERSON) |
| `email` | `?string` | Primary e-mail address |
| `getDisplayName()` | `string` | Company name or "First Last" depending on `partyType` |

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
