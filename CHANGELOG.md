# Changelog

All notable changes to this library are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [Unreleased] — Branch `WeclappAPIv2`

### Added — RecurringInvoiceResource (read-only, `/api/v2/recurringInvoice`)

Wraps weclapp's **recurring invoice** (wiederkehrende Rechnung) entity — the
template weclapp uses to auto-generate sales invoices at a fixed interval. It is
the authoritative source for a managed-service contract's billing cadence
(monthly, yearly, …) and the billed positions/quantities/amounts per customer.
This interval was previously unreadable via the library (not on SalesInvoice, not
on Article; `contractBillingCycle` is empty in practice; `InvoicingType` only knows
EFFORT/FIXED_PRICE).

**New files:**
- `src/DTO/RecurringInvoiceDTO.php` + `src/DTO/RecurringInvoiceItemDTO.php`
- `src/Enum/RecurringInvoiceIntervalType.php`
- `src/Resource/RecurringInvoiceResource.php`
- `tests/Unit/Resource/RecurringInvoiceResourceTest.php` (18 tests)

**Client accessor:** `$client->recurringInvoices(): RecurringInvoiceResource`

**Read operations** (all verified live against the miralsoft tenant — 389 records):

| Method | Description |
|---|---|
| `find($id)` | Single recurring invoice (typed `RecurringInvoiceDTO`) |
| `list($query)` / `listAll($query)` | Paginated / all, with `QueryBuilder` filtering |
| `count($query)` | Total matching count |
| `findByCustomer($customerId)` | All recurring invoices of a customer |
| `findByNumber($number)` | By human-readable number (null if absent) |
| `findModifiedSince($since, $extra)` | Delta-sync |

**Structured interval (the central value-add):**

```php
$ri = $client->recurringInvoices()->find($id);
$ri->interval;               // 1
$ri->intervalType;           // "MONTHLY" (raw)
$ri->getIntervalType();      // RecurringInvoiceIntervalType::Monthly (or null if unmapped)
$ri->getCadenceLabel();      // "every 1 MONTHLY"
$ri->intervalDayOfMonth;     // 1
$ri->getNextInvoiceDate();   // ?DateTimeImmutable
$ri->desiredInvoiceStatus;   // "OPEN_ITEM_CREATED"

foreach ($ri->recurringInvoiceItems as $item) {
    $item->articleId; $item->getQuantity(); $item->getUnitPrice(); $item->getNetAmount();
}
```

`RecurringInvoiceIntervalType` covers `MONTHLY`/`YEARLY` (confirmed live — 289/100 in
the tenant) plus `DAILY`/`WEEKLY`/`QUARTERLY`/`HALF_YEARLY` defensively;
`getIntervalType()` returns null for any unmapped value while the raw string stays
available.

**Read-only by design — verified against the live API:** the endpoint returns
`Allow: GET, HEAD, OPTIONS`; POST/PUT/DELETE all yield **HTTP 405**. There is no
create/update/delete (the consumer's "create-only later" is impossible — the API
forbids writes). The inherited `create()`/`update()`/`delete()` are overridden to
throw a clear `\LogicException` rather than issue a request that always fails.

Notes:
- The endpoint is **not described in the published OpenAPI document** but is fully
  functional on live tenants.
- There is **no explicit active/paused flag** in the schema; a future
  `nextInvoiceDate` is the practical signal that generation is ongoing.
- **Webhook (Trigger → Read):** `WebhookEntityName::RecurringInvoice` already exists;
  on a webhook event, re-read via `recurringInvoices()->find($payload['entityId'])`.

### Added — SalesInvoiceResource::findBySalesOrder()

New `findBySalesOrder(string $salesOrderId): array` — returns all `SalesInvoiceDTO`
belonging to a sales order. Slots in next to `findByCustomer()` / `findByInvoiceNumber()`.

**Encapsulates a weclapp filter quirk (live-verified against the miralsoft tenant):**
the `salesOrderId` field is **not filterable** — `salesOrderId-eq=…` is rejected with
HTTP 400 ("unexpected filter property"). The filterable path is the relation
sub-property `salesOrders.id`, which the method uses to filter **server-side**.

```php
$invoices = $client->salesInvoices()->findBySalesOrder($orderId);
// e.g. refresh the performance record (Leistungsnachweis) of each invoice
```

- **Fastest possible load:** a single paginated query (pageSize 1000) → exactly
  **one HTTP request** for any order with ≤ 1000 invoices (always true in practice).
  The server-side `salesOrders.id` filter returns only the matching invoices (e.g.
  1888 total → 1), not the whole collection. No preceding `count()` call.
- **Robust:** the result is verified client-side (zero extra requests) — an invoice
  belongs to the order if its `salesOrderId` matches *or* its `salesOrders[]` relation
  contains the ID. This mirrors the "server fuzzy → client exact" pattern of other
  `findBy*` methods and guards against a silently ignored/misunderstood filter.
- Returns an **empty list** when the order has no invoices — never throws on
  "nothing found". Returns immediately (no request) for an empty `$salesOrderId`.

5 new unit tests. (`SalesOrderResource::findByOrderNumber()` already existed, so the
optional symmetric helper required no change.)

### Added — Custom attribute (user-defined field) management

Full support for weclapp custom attributes (`customAttributes` / "benutzerdefinierte
Felder") — managing the **definitions** (the field schema) and reading/writing
**values** on any entity. Mirrors the Docbee library's `ensureDefinition` /
`setCustomFieldValue` / `getCustomFieldValue` ergonomics.

**All behaviours verified live against the miralsoft tenant** (definition
`docbeeTicketId`, id=998852, STRING, scoped to salesOrder):
ensure idempotency, atomic create-with-value + read-back, update-existing + read-back.

**New files:**
- `src/Enum/CustomAttributeType.php` — value types (STRING, INTEGER, DECIMAL, BOOLEAN, DATE, LIST, …)
- `src/Enum/CustomAttributeEntityType.php` — 24 entity types that can carry custom attributes
- `src/DTO/CustomAttributeDefinitionDTO.php` — maps `customAttributeDefinition`
- `src/Resource/CustomAttributeDefinitionResource.php` — wraps `/api/v2/customAttributeDefinition`
- 3 new unit test files (24 tests)

**Client method:** `$client->customAttributeDefinitions(): CustomAttributeDefinitionResource`

#### 1. Manage definitions

```php
use miralsoft\weclapp\api\Enum\CustomAttributeEntityType;
use miralsoft\weclapp\api\Enum\CustomAttributeType;

$defs = $client->customAttributeDefinitions();

// Idempotent ensure — creates if missing, reuses if present (returns the definition incl. id)
$def = $defs->ensure(
    CustomAttributeEntityType::SalesOrder,
    'docbeeTicketId',
    'Docbee Ticket ID',
    CustomAttributeType::String,
);

// List/find for a setup-UI dropdown
$forSalesOrder = $defs->findByEntity(CustomAttributeEntityType::SalesOrder);
$single        = $defs->findByKey('docbeeTicketId', CustomAttributeEntityType::SalesOrder);
```

| Method | Description |
|---|---|
| `ensure($entity, $key, $label, $type = String)` | Idempotent create-or-find; returns definition with `id` |
| `findByEntity($entity)` | All definitions scoped to an entity (client-side filtered) |
| `findByKey($key, $entity = null)` | Single definition by `attributeKey` |
| `find($id)` / `listAll()` / `count()` | Read (typed to `CustomAttributeDefinitionDTO`) |
| `create($data)` / `update($id, $data)` | Create / update (typed) |
| `delete($id)` | Delete a definition — ⚠️ destructive (removes the field + all its values across every entity); documented with a warning |
| `readOrder($entity)` / `updateOrder($entity, $order)` | Read/set the UI display order of custom fields per entity (`CustomAttributeDefinitionOrderDTO`) |

This covers the **complete** `/customAttributeDefinition` endpoint surface
(GET, POST, GET/{id}, PUT/{id}, DELETE/{id}, /count, /readOrder, /updateOrder).
`readOrder` verified live; `delete` is exposed but flagged destructive — schema
deletion should originate from a human operator, not automated sync.

#### 2. Write values — atomic on create, or Read-Modify-Write on update

```php
use miralsoft\weclapp\api\DTO\CustomAttributeDTO;

// Atomic: set the value in the same create call
$order = $client->salesOrders()->create([
    'customerId'       => $customerId,
    'customAttributes' => [CustomAttributeDTO::string($def->id, 'TICKET-4711')],
]);

// On an existing record: setCustomAttribute() preserves all other fields
$client->salesOrders()->setCustomAttribute(
    $orderId,
    CustomAttributeDTO::string($def->id, 'TICKET-4711'),
);
```

Typed payload builders on `CustomAttributeDTO` produce the correct value field per
type — the caller never assembles the raw payload manually:
`string()`, `number()`, `boolean()`, `date()` (accepts `DateTimeInterface` or epoch ms,
ms-precise), `selection()`.

`setCustomAttribute()` lives on `AbstractResource`, so it works for **every** entity
(salesOrder, article, customer, …). It uses the same safe Read-Modify-Write strategy
as `patch()` (full raw payload, optimistic-locking-safe, dry-run compatible).

#### 3. Read values

```php
$order  = $client->salesOrders()->find($orderId);

$ticket = $order->getCustomAttribute($def->id)?->stringValue;   // typed read (recommended)
$value  = $order->getCustomAttributeValue($def->id);            // best-effort scalar
```

`getCustomAttribute()` / `getCustomAttributeValue()` are available on **any** entity
DTO that maps `customAttributes` (SalesOrderDTO, CustomerDTO, ArticleDTO, …);
they return `null` on DTOs without custom attributes.

> weclapp returns `customAttributes` in both single-record (`find`) and list
> responses by default — no field-selector needed. Values are reliably present.

#### Important API notes (discovered during live verification)

- **Entity scope uses the `entities` array** (`["salesOrder"]`), NOT a separate
  `attributeEntityType` field. Passing `attributeEntityType` on create fails
  validation (`attributeEntityType must be empty for attributeType`). `ensure()`
  handles this correctly.
- **`entities` is not server-side filterable** — `findByEntity()` / `findByKey()`
  query by `attributeKey` and filter the entity scope client-side.
- **`attributeKey` is freely choosable** (e.g. `docbeeTicketId`) and is the stable
  idempotency handle.

### Added — QuantityUnitResource (`/api/v2/unit`)

New resource for reading and managing weclapp units of measure (Mengeneinheiten).
Primary use case: populating a setup-UI dropdown with time-based units for
time-tracking sync workflows (e.g. Docbee Exporter Zeit-Sync).

**New files:**
- `src/DTO/QuantityUnitDTO.php` — 7 fields: `id`, `version`, `createdDate`,
  `lastModifiedDate`, `name`, `description`, `timeUnitAmount`
- `src/Resource/QuantityUnitResource.php` — wraps `/api/v2/unit`
- `tests/Unit/Resource/QuantityUnitResourceTest.php` — 15 tests

**Client method:** `$client->quantityUnits(): QuantityUnitResource`

**Key finding — `timeUnitAmount` is in seconds (verified against live API):**

```
Stunde (h)  → timeUnitAmount = 3600   (= 3600 s = 1 h)
Stk., Kg, Lizenz, Gerät, … → timeUnitAmount absent (null)
Jahr, Monat → timeUnitAmount absent   (despite being time-related)
```

The unit is **seconds**, not milliseconds. Use `getMilliseconds()` to obtain
the value in ms directly (e.g. for a `msPerUnit` setup field):

```php
$unit->timeUnitAmount  // 3600  (s)
$unit->getMilliseconds() // 3_600_000  (ms)
```

**`QuantityUnitDTO` helpers:**

| Method | Description |
|---|---|
| `isTimeUnit(): bool` | `true` when `timeUnitAmount !== null` — filters out Stück, Pauschal, Lizenz, Jahr, Monat, … |
| `getMilliseconds(): ?int` | `timeUnitAmount × 1000`; null for non-time units |
| `getCreatedAt(): ?DateTimeImmutable` | epoch-ms → DateTimeImmutable |
| `getLastModifiedAt(): ?DateTimeImmutable` | epoch-ms → DateTimeImmutable |

**`QuantityUnitResource` methods:**

| Method | Description |
|---|---|
| `find(string $id): QuantityUnitDTO` | Fetch a single unit by ID |
| `listAll(?QueryBuilder): list<QuantityUnitDTO>` | All units (cacheable) |
| `findByName(string $name): QuantityUnitDTO` | Exact name match; throws `NotFoundException` |
| `findTimeUnits(): list<QuantityUnitDTO>` | Only units where `isTimeUnit() === true` |
| `create(array): QuantityUnitDTO` | Create a new unit |
| `update(string, array): QuantityUnitDTO` | Update an existing unit |
| `delete(string): void` | Delete a unit |

**Usage example (Docbee Exporter setup dropdown):**

```php
$timeUnits = $client->quantityUnits()->findTimeUnits();
foreach ($timeUnits as $unit) {
    echo "{$unit->description} ({$unit->name}) = {$unit->getMilliseconds()} ms\n";
    // "Stunde (h) = 3600000 ms"
}
```

**Docbee fail-soft guard** (`method_exists` check works automatically):

```php
if (method_exists($client, 'quantityUnits')) {
    $units = $client->quantityUnits()->findTimeUnits();
}
```

### Fixed — QueryBuilder::toEpochMs() millisecond-precision loss

`modifiedSince()` and `createdSince()` accept a `DateTimeInterface` value. The
previous implementation converted it to epoch milliseconds via
`getTimestamp() * 1000`, which silently discards any sub-second component:

```php
// BEFORE (buggy) — 74 ms silently lost:
$value->getTimestamp() * 1000;  // 1779799373000 instead of 1779799373074
```

The bug caused delta-sync watermarks to be rounded down to the nearest whole
second. The last record processed in the previous run had `lastModifiedDate =
1779799373074` ms; the next run fetched with `lastModifiedDate-gt=1779799373000`,
causing that record to re-appear and be double-processed.

**Fix:** Replace `getTimestamp()` with millisecond-precise arithmetic:

```php
// AFTER — no sub-second loss:
(int) $value->format('U') * 1000 + (int) $value->format('v');
//   format('U') → Unix epoch in whole seconds (exact integer string, no float)
//   format('v') → milliseconds component 000–999 (PHP 7.1+)
```

`getTimestamp() * 1000` was intentionally avoided in the fix because
`getTimestamp()` always returns whole seconds, whereas `format('U')` returns the
same integer but as a string — the explicit `(int)` cast guarantees integer
multiplication with no float-precision risk.

**Round-trip acceptance test:**

```php
$ms = 1779799373074;
$dt = DateTimeImmutable::createFromFormat('U.u', sprintf('%d.%03d', intdiv($ms, 1000), $ms % 1000));
// toEpochMs($dt) === 1779799373074  ✓  (74 ms preserved)
```

**New / updated unit tests** (`QueryBuilderTest`):
- `test_modified_since_with_datetime` — updated formula to match canonical implementation
- `test_modified_since_preserves_sub_second_precision` — exact round-trip with `$ms = 1779799373074`
- `test_created_since_with_sub_second_datetime` — verifies `createdSince()` code path
- `test_modified_since_with_epoch_ms_preserves_milliseconds` — integer pass-through

### Added — AbstractResource::findRaw() — public raw record backup

`findRaw(string $id): array` was previously `protected`. It is now `public` and
available on every resource class (typed override added on `ArticleResource`).

**Purpose:** Return the complete, unfiltered weclapp API response as a plain PHP
array — every field the API provides, including read-only system fields
(`statusHistory`, `shipped`, `currencyConversionDate`, …) that the DTO does not
map. The result can be stored as a backup before a write operation and passed
unmodified to `update()` to restore the record to exactly this state.

```php
// Step 1 — take a full backup before any write
$backup = $client->articles()->findRaw($articleId);

// Step 2 — perform your write (patch, addOrderItem, custom update, …)
$client->articles()->patch($articleId, ['name' => $newName]);

// Step 3 — if something went wrong, restore:
//   (must merge the current version first to honour optimistic locking)
$current = $client->articles()->findRaw($articleId);
$client->articles()->update($articleId, array_merge($backup, ['version' => $current['version']]));
```

**Optimistic locking caveat:** The `version` field in the snapshot reflects the
state at backup time. If the record was modified between the backup and the
restore, weclapp rejects the PUT with HTTP 409 (`OptimisticLockException`).
Always merge the current `version` into the backup before calling `update()`.

**Note on absent fields:** weclapp omits optional fields whose value is `null`
or empty from GET responses. The raw array therefore does not always contain
every key the DTO maps (the DTO fills those absent keys with defaults). This
does not affect backup/restore correctness: a PUT without an absent field leaves
that field at its current value, which was already `null`.

**`ArticleWriteIntegrationTest`** — two additional tests:

- `test_find_raw_round_trip_preserves_all_business_fields` — load raw → `update()`
  unchanged → re-fetch and assert `articleNumber`, `name`, `active`, `articleCategoryId`
  are all identical (verifies the round-trip is truly lossless for business data)
- `test_find_raw_contains_all_api_fields` — asserts required fields are present,
  verifies no data transformation occurs in `findRaw()` (raw values match DTO values
  for every shared key), and confirms the response is substantially richer than a stub

### Added — AbstractResource::patch() — safe partial field update (Read-Modify-Write)

All resource classes now expose a `patch()` method for changing individual fields
on an existing record without affecting any other field.

**Problem solved:** weclapp's `PUT` endpoint treats absent fields as "reset to
null/default". A call to `update($id, ['name' => 'New'])` would silently erase
every other field on the record (description, category, prices, EAN, …). The
only safe alternative was to fetch the full record first — but this required
callers to implement the Read-Modify-Write pattern themselves.

**Solution:** `patch()` encapsulates the pattern:

```php
// Change only the name — all other fields are preserved automatically
$article = $client->articles()->patch($id, ['name' => 'Corrected Name']);

// Works on any resource that doesn't override it
$customer = $client->customers()->patch($id, ['company' => 'New GmbH']);
```

**Behaviour:**
1. `findRaw($id)` — fetches the complete raw record including all read-only system
   fields (`statusHistory`, `shipped`, `currencyConversionDate`, …)
2. Merges `$fields` on top; `id` and `version` from `$fields` are silently stripped
   (always taken from the live GET to prevent stale-version errors)
3. `update($id, $merged)` — PUTs the full merged payload back

**Optimistic locking:** If another process modifies the record between the GET and
the PUT, weclapp returns HTTP 409 → `OptimisticLockException`. The caller must
re-fetch and retry.

**Dry-run compatible:** The GET is always real; the PUT honours `withDryRun()`.

```php
// Validate the patch without persisting
$preview = $client->articles()->withDryRun()->patch($id, ['name' => 'Preview']);
// $preview->id === '' (not saved), name reflects submitted value
```

**Typed override on `ArticleResource`:** Returns `ArticleDTO` (not `AbstractDTO`).
Further resource-specific overrides can be added on demand.

**`ArticleWriteIntegrationTest`** (requires `WECLAPP_ALLOW_WRITES=true`) verifies:
- Round-trip: load article → patch name → assert other fields unchanged → re-fetch confirms persistence → restore original name
- Dry-run variant: PUT not persisted, name unchanged on re-fetch
- Empty `$fields` → `InvalidArgumentException`

### Fixed — SalesOrderResource Read-Modify-Write methods sent incomplete payload

`addOrderItem()`, `updateOrderItem()` and `removeOrderItem()` were broken in both
dry-run and production mode. They submitted a partial payload containing only
`version` and `orderItems`, causing weclapp to reject the PUT with:

> HTTP 400 — property `statusHistory` is read-only

**Root cause:** weclapp treats absent fields in a PUT payload as "reset to null".
When a null is assigned to a read-only field such as `statusHistory`, `shipped` or
`currencyConversionDate`, weclapp raises a validation error even though the value
was not intentionally changed.

**Fix:** All three methods now call the new `AbstractResource::findRaw()` helper to
obtain the complete, unmodified API response as a plain array, mutate only the
`orderItems` key, and PUT the full record back. weclapp sees no change to the
read-only fields and the validation passes.

**New protected method:** `AbstractResource::findRaw(string $id): array` — fetches
a record as a raw associative array (bypassing DTO hydration), intended exclusively
for Read-Modify-Write operations that must round-trip all fields.

### Fixed — SalesInvoiceResource::resolveCustomerDisplayName() undefined property access

`resolveCustomerDisplayName()` referenced three properties that do not exist on
`SalesInvoiceDTO`: `$customerName`, `$partyId`, and `$customerNumber`. At runtime
these produced PHP notices ("Undefined property") and the fallback chain silently
returned `'Unknown'` for every invoice.

**Fix:** The method now uses `$invoice->customerId` (the only customer identifier
present on `SalesInvoiceDTO`) to call `fetchParty()` and delegates display-name
resolution to `PartyDTO::getDisplayName()`.

### Fixed — WebhookResource::reactivate() now live-verified

The `reactivate()` docblock previously contained:

> ⚠️ The behaviour of PUT with `deactivatedDate: null` has NOT been verified
> against the live weclapp API.

A new write integration test (`test_reactivate_webhook_clears_deactivated_date`)
performs a full deactivate → reactivate cycle and confirms the behaviour against
the live API. The warning has been removed from the method docblock.

### Added — Write integration tests + dry-run integration tests

**`DryRunIntegrationTest`** (safe against any tenant, no `WECLAPP_ALLOW_WRITES` required):
Tests the dry-run feature end-to-end against the real weclapp API:
- Customer update (round-trip validate) and field-reflection
- SalesOrder create with valid / invalid customerId
- `addOrderItem()` via Read-Modify-Write: real GET + dry-run PUT
- Quotation create + update, Article create + update
- SalesInvoice update, Supplier create + update, Contact create + update

**`WebhookWriteIntegrationTest`** (opt-in: requires `WECLAPP_ALLOW_WRITES=true`):
Write-capable integration tests. All tests clean up after themselves (try/finally)
even if assertions fail, keeping the tenant clean.

```bash
WECLAPP_ALLOW_WRITES=true php vendor/bin/phpunit --testsuite Write
```

- Full Create → Update → Delete lifecycle
- `ensureSubscription()` — idempotency, flag merging
- `deactivate()` + `reactivate()` full cycle (live-verified)
- `findByUrl()` on a freshly created webhook

**`phpunit.xml`:** Separate `Write` testsuite; credentials loaded from `tests/.env.test`.

**Test fixture anchors** — `IntegrationTestCase` resolves two optional env vars once per
PHPUnit process and caches the result, giving all integration tests stable, named test data:

```ini
# tests/.env.test
WECLAPP_TEST_CUSTOMER_NUMBER=K-10042   # resolved once via findByCustomerNumber()
WECLAPP_TEST_SALES_ORDER_NUMBER=SO-123 # resolved once via findByOrderNumber()
```

The `SalesOrderDTO.customerId` of the test order is reused for all create-dry-run tests
(SalesOrder, Quotation, Contact), eliminating "invalid customerId" failures that occurred
when fallback code picked an arbitrary first customer from a list.

**Expanded integration test coverage — 12 additional test methods across 5 files:**

| New test | File | What it verifies |
|---|---|---|
| `test_find_by_customer_number_returns_same_record` | Customer | Happy path, uses `WECLAPP_TEST_CUSTOMER_NUMBER` anchor |
| `test_find_by_customer_number_throws_not_found_for_unknown` | Customer | `NotFoundException` on bogus number |
| `test_find_by_customer_returns_sales_order_dtos` | SalesOrder | Filter by `customerId`; anchor order must appear in result |
| `test_find_by_status_returns_sales_order_dtos` | SalesOrder | All returned items have the requested status |
| `test_find_credit_notes_returns_sales_invoice_dtos` | SalesInvoice | Only `CREDIT_NOTE` type returned |
| `test_resolve_customer_display_name_returns_non_empty_string` | SalesInvoice | Party lookup + `getDisplayName()` |
| `test_load_from_stubs_returns_contact_dtos` | Contact | Resolves `CustomerDTO.$contacts` stubs to full DTOs |
| `test_cursor_yields_article_dtos` | Article | Generator paginates lazily; forced page boundary (pageSize 2) |
| `test_reactivate_webhook_clears_deactivated_date` | WebhookWrite | Full deactivate → reactivate cycle, re-fetch confirms persistence |

### Added — Dry-run mode (`AbstractResource::withDryRun()`)

All resource classes now support weclapp's built-in dry-run mechanism
(`?dryRun=true` query parameter) for validating write operations without
persisting data.

**API:**

```php
$resource = $client->salesOrders()->withDryRun(); // returns a clone, original unchanged
```

`withDryRun()` returns a lightweight clone of the resource with dry-run mode
enabled. The original instance is **never mutated** — successive calls to the
same `$client->salesOrders()` always return a fresh, non-dry-run resource.
Use `withDryRun(false)` to get a non-dry-run clone of an already-dry-run resource.

**Affected methods:** `create()`, `update()`, `delete()`.
Read operations (`find`, `list`, `listAll`, `cursor`, etc.) are unaffected.

**Response behaviour:**
- `create()` / `update()`: weclapp returns `HTTP 200` (instead of 201) with the
  full entity body minus `id`, `version`, `createdDate`, `lastModifiedDate`.
  Those four fields will be `''` / `0` in the returned DTO (the DTO default for
  absent string/int fields). All business-computed fields are populated.
- `delete()`: weclapp returns `HTTP 200` (instead of 204); the response body is
  silently ignored and `void` is returned, consistent with a normal delete.
- Errors (`400`, `404`, `409`, …) are thrown as the same typed exceptions as in
  a real call — `ValidationException`, `NotFoundException`,
  `OptimisticLockException`, etc.

**Examples:**

```php
// Validate a new sales order without saving it
$order = $client->salesOrders()->withDryRun()->create([
    'customerId' => 'cust-123',
    'orderItems' => [['articleId' => 'art-1', 'quantity' => '2.00']],
]);
// $order->id === '' (not persisted), $order->status and other fields populated

// Test whether an update would pass validation
try {
    $client->customers()->withDryRun()->update($id, ['vatId' => 'INVALID']);
    echo 'Update would succeed.';
} catch (ValidationException $e) {
    echo 'Would fail: ' . implode(', ', $e->getErrors());
}

// Verify a delete would not be rejected by business rules
try {
    $client->articles()->withDryRun()->delete($articleId);
    echo 'Safe to delete.';
} catch (WeclappApiException $e) {
    echo 'Cannot delete: ' . $e->getMessage();
}

// isDryRun() helper for conditional logic
$res = $client->salesOrders()->withDryRun();
if ($res->isDryRun()) { /* ... */ }
```

**Implementation:** `withDryRun()` is defined on `AbstractResource` and is
therefore available on every resource class (`customers()`, `salesOrders()`,
`articles()`, etc.) with no per-resource changes required. The `bool $dryRun`
flag is propagated to `HttpClient::post()`, `HttpClient::put()`, and
`HttpClient::delete()`, each of which appends `?dryRun=true` to the URL.

### Added — WebhookResource idempotent setup helpers + full entityName enum

**`WebhookEntityName` enum — expanded from 10 to 121 cases**

Verified against a live weclapp tenant's webhook configuration UI on 2026-05-04.
The 117 confirmed values are organised into categories (Party/CRM, Articles,
Sales, Purchasing, Finance, HR, Tasks, Communication, Users, Misc).
Four legacy values retained from the previous library version are annotated as
potentially module-specific (`PurchaseOrder`, `Shipment`, `Ticket`, `Contract`).
Beta-quality entity names are marked with `@beta` in their case docblock.

Key findings documented in the enum:
- `customer`, `contact` and `party` are **distinct** entityNames — all three exist.
- There is **no** `salesOrderItem` entityName — item changes fire as `salesOrder` update events.
- `webhook` itself is a valid entityName (meta-subscriptions).

**`WebhookResource` — 6 new methods**

- **`ensureSubscription(string $entityName, string $url, bool $atCreate, bool $atUpdate, bool $atDelete, string $requestMethod): WebhookDTO`**

  Idempotent upsert: creates the subscription if it does not exist, returns the
  existing one unchanged if all requested flags are already set, or merges flags
  additively and updates if a new flag is needed. Safe to call on every application
  start with no duplicate subscriptions created.

  ```php
  // Safe to call repeatedly — only writes to the API when something actually changes
  $client->webhooks()->ensureSubscription(
      entityName: WebhookEntityName::SalesOrder->value,
      url:        'https://my-app.example.com/webhooks/weclapp',
      atCreate:   true,
      atUpdate:   true,
  );
  ```

- **`findByUrl(string $url): list<WebhookDTO>`** — all subscriptions pointing to a given URL (exact match, client-side filter).
- **`findByEntityName(string $entityName): list<WebhookDTO>`** — all subscriptions for a given entity type (client-side filter).
- **`deactivate(string $id): WebhookDTO`** — soft-deactivates a webhook by setting `deactivatedDate`; idempotent.
- **`reactivate(string $id): WebhookDTO`** — clears `deactivatedDate`; idempotent. ⚠️ Not yet verified against live API — see method docblock.
- **`delete(string $id): void`** — permanent deletion (explicit typed override).

**Research documentation** added as a block comment in `WebhookResource`:
endpoint, HTTP methods, schema, entityName findings, incoming payload structure
(unconfirmed — log live traffic to verify), deactivation mechanics, no-HMAC note.

### Added — SalesOrderResource item operations (Read-Modify-Write)

Three methods for modifying order line items in place. weclapp does not expose a
dedicated item endpoint — items are embedded in the order and must be written back
via `PUT salesOrder/id/{id}`. All three methods follow the same
**Read → Mutate → Write** pattern and carry the current `version` field to satisfy
weclapp's optimistic locking requirement.

If another process modifies the order between the GET and the PUT, weclapp responds
with HTTP 409 and an `OptimisticLockException` is thrown. The caller is expected to
re-fetch the order and retry.

- **`SalesOrderResource::addOrderItem(string $orderId, array $data): SalesOrderDTO`**

  Appends a new line item to the order. Requires at least one of `articleId` or
  `title` in `$data`; all other fields (quantity, unitPrice, taxId, …) are optional.
  Throws `\InvalidArgumentException` if neither field is provided.

  ```php
  $updated = $client->salesOrders()->addOrderItem($orderId, [
      'articleId' => 'art-42',
      'quantity'  => '3.00',
  ]);
  ```

- **`SalesOrderResource::updateOrderItem(string $orderId, string $itemId, array $data): SalesOrderDTO`**

  Merges `$data` into the existing item identified by `$itemId`. Only the fields
  present in `$data` change; all other item fields retain their current values.
  Throws `NotFoundException` if no item with that ID exists in the order.

  ```php
  $updated = $client->salesOrders()->updateOrderItem($orderId, $itemId, [
      'quantity'  => '5.00',
      'unitPrice' => '8.50',
  ]);
  ```

- **`SalesOrderResource::removeOrderItem(string $orderId, string $itemId): SalesOrderDTO`**

  Removes the item identified by `$itemId` from the order.
  Throws `NotFoundException` if no item with that ID exists.

  ```php
  $updated = $client->salesOrders()->removeOrderItem($orderId, $itemId);
  ```

### Added — findByNumber() convenience lookups (SalesOrder, SalesInvoice, Quotation)

Human-readable number lookup on the three main sales document resources.
All three follow the same pattern: `list()` with an equality filter on the number field,
`pageSize(1)`, and throw `NotFoundException` if the result set is empty.

- **`SalesOrderResource::findByOrderNumber(string $orderNumber): SalesOrderDTO`**

  Finds a sales order by its UI-visible order number (e.g. `"SO-10042"`).

  ```php
  $order = $client->salesOrders()->findByOrderNumber('SO-10042');
  ```

- **`SalesInvoiceResource::findByInvoiceNumber(string $invoiceNumber): SalesInvoiceDTO`**

  Finds a sales invoice by its invoice number (e.g. `"RE-10042"`, `"CLX-1061"`, `"PR-0007"`).
  Works for all invoice types including credit notes and proforma invoices.

  ```php
  $invoice = $client->salesInvoices()->findByInvoiceNumber('RE-10042');
  ```

- **`QuotationResource::findByQuotationNumber(string $quotationNumber): QuotationDTO`**

  Finds a quotation by its quotation number (e.g. `"ANG-10042"`).

  ```php
  $quotation = $client->quotations()->findByQuotationNumber('ANG-10042');
  ```

### Added — ArticleResource::findCategoryIdByNumber()

- **`ArticleResource::findCategoryIdByNumber(string $articleNumber): ?string`**

  Convenience wrapper: looks up an article by its SKU and returns its
  `articleCategoryId`. Returns `null` when the article has no category assigned.
  Throws `NotFoundException` when no article with that number exists.

  ```php
  $categoryId = $client->articles()->findCategoryIdByNumber('ART-10042');
  // → "cat-uuid" or null
  ```

### Fixed — getDisplayName() uses partyType instead of company presence

All three party-based DTOs (`CustomerDTO`, `PartyDTO`, `SupplierDTO`) checked
`company !== null` to decide between ORGANIZATION and PERSON rendering. A PERSON
customer/supplier who has a non-null `company` field (their employer) would have had
the company name returned instead of "First Last" — semantically wrong and a silent
data error in any downstream system.

**Changes:**

- **`CustomerDTO::getDisplayName()`** — condition changed from `company !== null` to
  `partyType === 'ORGANIZATION'`; fallback chain extended:
  - ORGANIZATION: `company ?? customerNumber ?? ''`
  - PERSON: `"firstName lastName"` → `company` (employer) → `customerNumber` → `''`

- **`PartyDTO::getDisplayName()`** — same fix; number fallback:
  `customerNumber ?? supplierNumber ?? ''` (PartyDTO covers both party roles)

- **`SupplierDTO::getDisplayName()`** — same fix; fallback chain:
  - ORGANIZATION: `company ?? supplierNumber ?? ''`
  - PERSON: `"firstName lastName"` → `company` → `supplierNumber` → `''`

- **`ContactDTO::getDisplayName()`** — added as a thin alias for `getFullName()`.
  Contacts are always `PERSON` type, so the result is always `"firstName lastName"`.
  Provides a consistent `getDisplayName()` surface across all four party DTOs.
  `getFullName()` is retained for backward compatibility.

### Added — ContactResource::loadFromStubs()

- **`ContactResource::loadFromStubs(list<array> $stubs): list<ContactDTO>`** —
  resolves the raw contact stubs embedded in `CustomerDTO::$contacts` to full
  `ContactDTO` objects.

  **Background:** The weclapp API embeds contacts in a customer response as stubs —
  `[{"id": "975300"}]` — with no other fields populated. Filtering via
  `parentPartyId-eq` does not work either, because weclapp returns contact objects with
  `parentPartyId: null` even when the contact is linked to a parent organisation.
  Loading each contact individually by ID via `find()` is the only reliable approach.

  ```php
  $customer  = $client->customers()->find($id);
  $contacts  = $client->contacts()->loadFromStubs($customer->contacts);
  // → list<ContactDTO> with all fields populated
  ```

  Stubs with a missing or malformed `id` key are silently skipped.
  Stubs whose `find()` call fails (e.g. deleted contact) are also skipped — the
  remaining contacts are still returned.

### Deprecated — ContactResource::findByParentPartyId()

- **`ContactResource::findByParentPartyId(string $parentPartyId)`** — marked `@deprecated`.
  Verified non-functional: the weclapp API returns contact party objects with
  `parentPartyId: null` even for contacts that are genuinely linked to a parent
  organisation. The `parentPartyId-eq` filter therefore always returns zero results.

  **Use `loadFromStubs()` instead** — pass `CustomerDTO::$contacts` directly:
  ```php
  // Before — always returns [] in practice:
  $contacts = $client->contacts()->findByParentPartyId($customer->id);

  // After — correct:
  $contacts = $client->contacts()->loadFromStubs($customer->contacts);
  ```

### Deprecated — ContactResource::findByCustomer()

- **`ContactResource::findByCustomer(string $customerId)`** — marked `@deprecated`.
  The method filters on the party field `customerId`, which is **not** the party UUID
  of the parent organisation. Callers who pass `CustomerDTO::$id` (the party UUID)
  silently receive an empty list in most tenants.

  **Use `loadFromStubs()` instead:**
  ```php
  // Before (broken):
  $contacts = $client->contacts()->findByCustomer($customer->id);

  // After (correct):
  $contacts = $client->contacts()->loadFromStubs($customer->contacts);
  ```

  Both deprecated methods are retained for backward compatibility and will be removed
  in a future major release.

### Fixed — Enum gaps discovered by live integration tests

- **`SalesOrderStatus`** — added `CLOSED` case. Some weclapp tenants put fulfilled orders
  into a `CLOSED` state that was not previously covered by the enum. The status-enum test
  in `SalesOrderResourceIntegrationTest` now passes for those tenants.

- **`QuotationStatus`** — added short-form aliases for the five standard states:
  `ACCEPTED`, `REJECTED`, `IN_PROCESS`, `SENT`, `EXPIRED`.
  Some weclapp tenants return these values without the `QUOTATION_` prefix. The live test
  caught `ACCEPTED` and `REJECTED` in this tenant; the remaining aliases were added
  pre-emptively to prevent future failures.

### Added — Integration test suite (live API tests)

- **`tests/Integration/IntegrationTestCase`** — base class for all live API tests.
  Loads `tests/.env.test` automatically (gitignored), creates a `WeclappClient` from
  `WECLAPP_TENANT` + `WECLAPP_TOKEN` env vars, and calls `markTestSkipped()` when
  credentials are absent — no failures without credentials.

- **`tests/Integration/Resource/CustomerResourceIntegrationTest`** — verifies list,
  find-by-id, count-matches-total and `modifiedSince` filter against the live API.

- **`tests/Integration/Resource/SalesInvoiceResourceIntegrationTest`** — verifies list,
  find-by-id, `SalesInvoiceStatus` enum coverage (flags unknown statuses as test failures
  so new API values are caught early) and count-matches-total.

- **`tests/Integration/Resource/ArticleResourceIntegrationTest`** — verifies list,
  find-by-article-number, that `NotFoundException` is thrown for unknown numbers and
  count-matches-total.

- **`tests/Integration/Resource/NumberRangeResourceIntegrationTest`** — verifies that
  at least one number range exists, that all returned types are known enum values
  (emits a warning for unknown ones), that values exist for each range and that
  `isCurrentlyActive()` returns a bool without throwing.

- **`tests/.env.test.example`** — updated template: only `WECLAPP_TENANT` + `WECLAPP_TOKEN`
  are required (URI is now derived automatically; `WECLAPP_URI` removed).

- **`phpunit.xml`** — `Integration` testsuite added alongside `Unit`. Run selectively:
  `phpunit --testsuite Unit` (fast, offline) · `phpunit --testsuite Integration` (live).

### Fixed — Security: hardcoded credentials and parse_str() removed

- **`tests/configWeclapp.php`** — hardcoded production API token removed; credentials
  are now read from `WECLAPP_TENANT` / `WECLAPP_TOKEN` env vars; the v1 URI is derived
  from the tenant subdomain automatically.

- **`src/Resource/DocumentResource::findByEntity()`** — replaced `parse_str()` round-trip
  with direct query-string concatenation (`http_build_query` + `&` append). The extra
  QueryBuilder fragment is built once outside the pagination loop.

- **`.gitignore`** — `tests/.env.test`, `.env`, `.env.*.local`, `phpunit.xml.local` and
  `.phpunit.result.cache` added so local credential files can never be committed.

### Added — Number Range support (proforma invoice detection for DATEV)

- **`NumberRangeType` enum** — all 45 entity types that have a configurable number range in
  weclapp (e.g. `SALES_INVOICE`, `PROFORMA_INVOICE`, `SALES_INVOICE_CANCELLATION`).
  Key insight: proforma invoices are **NOT** identified by `salesInvoiceType` — that enum
  contains no proforma value in the weclapp API spec. Proforma invoices are distinguished
  exclusively by their `invoiceNumber` prefix (e.g. `"PR-"`) which is derived from the
  `PROFORMA_INVOICE` number range configuration, queryable at runtime.

- **`NumberRangeDTO`** — maps the `numberRange` schema (`/api/v2/numberRange`).
  Fields: identity (`id`, `version`, `createdDate`, `lastModifiedDate`) + `type`
  (a `numberRangeType` string value).
  Helper: `getType(): ?NumberRangeType` — returns the typed enum case.

- **`NumberRangeValueDTO`** — maps the `numberRangeValue` schema (`/api/v2/numberRangeValue`).
  All 12 fields beyond identity: `numberRangeId`, `interval`, `lastValue`, `length`,
  `prefix` (e.g. `"PR-"`), `suffix`, `validFromDate`, `validToDate`,
  `salesInvoiceTypes` (`list<string>`), `creditNoteInvoiceTypes` (`list<string>`),
  `salesChannels`, `articleCategories`.
  Helper methods:
  - `isCurrentlyActive(): bool` — `true` if today falls within the validity period
  - `getValidFrom(): ?DateTimeImmutable`, `getValidTo(): ?DateTimeImmutable`
  - `formatNextNumber(): string` — formats the next number (e.g. `"PR-0042"`) based
    on `lastValue + interval`, optionally zero-padded to `length` digits

- **`NumberRangeResource`** — read-only resource for `/api/v2/numberRange`.
  Methods:
  - `find(string $id): NumberRangeDTO`
  - `findByType(NumberRangeType|string $type): ?NumberRangeDTO` — returns the range
    configured for the given entity type, or `null` if not configured
  - `getProformaInvoicePrefix(): ?string` — two-step API lookup:
    1. `GET /numberRange?type-eq=PROFORMA_INVOICE` — find the proforma range
    2. `GET /numberRangeValue?numberRangeId-eq={id}` — read the configured prefix
    Returns the tenant-specific prefix (e.g. `"PR-"`) or `null` if not configured.
    When multiple values exist, the currently active one is preferred.

- **`NumberRangeValueResource`** — read-only resource for `/api/v2/numberRangeValue`.
  Methods:
  - `find(string $id): NumberRangeValueDTO`
  - `findByNumberRange(string $numberRangeId): list<NumberRangeValueDTO>` — all value
    entries for a given range (multiple can exist for different channels / periods)
  - `findPrefix(string $numberRangeId): ?string` — prefix of the currently active value

- **`WeclappClient::numberRanges()`** — factory for `NumberRangeResource`.
- **`WeclappClient::numberRangeValues()`** — factory for `NumberRangeValueResource`.

**Design rationale:** `isProformaInvoice()` was deliberately NOT added to `SalesInvoiceDTO`.
The API's `salesInvoiceType` enum contains no proforma-specific value, so any detection
based on that field would permanently return `false`. The only reliable signal is the
`invoiceNumber` prefix, which is tenant-configurable. Embedding a hardcoded prefix like
`"PR-"` in the DTO would violate the 1:1 API mirror principle and break silently whenever
a tenant customises their number range. The correct approach:
```php
// Once at startup — cache the result
$prefix = $client->numberRanges()->getProformaInvoicePrefix(); // e.g. "PR-"

// When processing invoices for DATEV
$forDatev = array_filter(
    $client->salesInvoices()->listAll(),
    fn($inv) => $prefix === null || !str_starts_with($inv->invoiceNumber, $prefix)
);
```

---

### Added — DropshippingFormTextsDTO + DTO field corrections

- **`DropshippingFormTextsDTO`** — new typed DTO for the `dropshippingDeliveryNoteFormTextBlockData`
  schema embedded in `PurchaseOrderDTO::$dropshippingDeliveryNoteFormTexts`.
  3 fields: `recordComment`, `recordFreeText`, `recordOpening` (all `?string`).
  Helper: `isEmpty(): bool`.

### Changed — DTO corrections (breaking)

All changes below were driven by a full audit against the OpenAPI spec.
Any field that was not in the spec has been removed from the corresponding DTO.

- **`EmailAddressesDTO`** — `$bccAddresses`, `$ccAddresses`, `$toAddresses` type changed from
  `array` to `?string`. The `emailAddresses` schema defines these as plain `string` fields
  (comma-separated addresses), not arrays.
  **Breaking**: replace array access with string access; use `getAllAddresses()` to get a
  split + deduplicated `list<string>`.
  `getAllAddresses()` now parses comma-separated values internally.
  `isEmpty()` now checks for null/empty string instead of empty array.

- **`PurchaseOrderDTO::$dropshippingDeliveryNoteFormTexts`** — type changed from `array` to
  `?DropshippingFormTextsDTO`. The spec defines this field as a single embedded object
  (`dropshippingDeliveryNoteFormTextBlockData`), not a collection.
  **Breaking**: access via `->dropshippingDeliveryNoteFormTexts->recordComment` etc.

- **`SalesOrderDTO`** — removed 3 fields that do not exist in the `salesOrder` API schema:
  - `$customerNumber` — not in spec; was denormalised from the linked customer record
  - `$customerName` — not in spec; not defined anywhere in the OpenAPI spec
  - `$customerOrderNumber` — not in spec; correct field is `$orderNumberAtCustomer` (kept)
  **Breaking**: remove all references to these properties.

- **`SalesInvoiceDTO`** — removed 5 fields that do not exist in the `salesInvoice` API schema:
  - `$customerNumber` — not in spec
  - `$partyId` — not in spec for `salesInvoice`
  - `$customerName` — not in spec
  - `$openAmount` — not in spec; open-amount data lives in the `salesOpenItem` endpoint
  - `$currency` — not in spec; use `$recordCurrencyId` to look up the currency
  Removed helper methods that depended on removed fields:
  `getCustomerDisplayName()`, `getOpenAmount()`, `isOpen()`.
  **Breaking**: remove all references to these properties and methods.

- **`QuotationDTO`** — removed 2 fields that do not exist in the `quotation` API schema:
  - `$customerName` — not in spec
  - `$currency` — not in spec; use `$recordCurrencyId`
  **Breaking**: remove all references to these properties.

---

### Added — RecordAddressDTO (correct schema for embedded document addresses)

- **`RecordAddressDTO`** — new DTO mapping the `recordAddress` schema used for embedded
  addresses on sales documents (`deliveryAddress`, `invoiceAddress`, `recordAddress` fields).
  Distinct from `AddressDTO` (`address` schema, 24 fields, with identity) in two key ways:
  - **No identity fields** — no `id`, `version`, `createdDate`, `lastModifiedDate`.
  - **Adds `middleName`** — a field absent from the `address` schema.
  All 18 fields are `?string` (none are required by the API schema):
  `city`, `company`, `company2`, `countryCode`, `firstName`, `globalLocationNumber`,
  `lastName`, `middleName`, `phoneNumber`, `postOfficeBoxCity`, `postOfficeBoxNumber`,
  `postOfficeBoxZipCode`, `salutation`, `state`, `street1`, `street2`, `titleId`, `zipcode`.
  Helper method: `getDisplayLine()` — single-line representation including middle name.

### Changed — RecordAddressDTO adoption in document DTOs (breaking)

- **`SalesOrderDTO`** — `$deliveryAddress`, `$invoiceAddress`, `$recordAddress` type changed
  from `?AddressDTO` to `?RecordAddressDTO`. The API returns the `recordAddress` schema for
  these fields, not the `address` schema; `AddressDTO` was incorrectly hydrating them and
  silently dropping `middleName` while demanding non-nullable id/version/timestamps.
  **Breaking**: any code that typed these as `AddressDTO` must be updated to `RecordAddressDTO`.

- **`SalesInvoiceDTO`** — `$deliveryAddress`, `$recordAddress` type changed from
  `?AddressDTO` to `?RecordAddressDTO`. Same rationale as above.
  **Breaking**: update type hints to `RecordAddressDTO`.

- **`QuotationDTO`** — `$deliveryAddress`, `$invoiceAddress`, `$recordAddress` type changed
  from `?AddressDTO` to `?RecordAddressDTO`. Same rationale.
  **Breaking**: update type hints to `RecordAddressDTO`.

- **`PurchaseOrderDTO`** — `$deliveryAddress`, `$invoiceAddress`, `$recordAddress` type changed
  from `?AddressDTO` to `?RecordAddressDTO`. The `purchaseOrder` schema also uses `recordAddress`
  for all three address fields (confirmed from OpenAPI spec).
  **Breaking**: update type hints to `RecordAddressDTO`.

- **`ShipmentDTO`** — `$invoiceAddress`, `$recipientAddress`, `$shippedFromAddress` type changed
  from `?AddressDTO` to `?RecordAddressDTO`. The `shipment` schema uses `recordAddress` for all
  three address fields (confirmed from OpenAPI spec).
  **Breaking**: update type hints to `RecordAddressDTO`.

- **`AddressDTO`** — class docblock corrected. Now explicitly documents that this DTO is only
  for entries in `$addresses` on party-based DTOs (PartyDTO, CustomerDTO etc.), and links
  to `RecordAddressDTO` for embedded document addresses.

---

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

### Added — Document Record DTO Completions

- **`EmailAddressesDTO`** — typed DTO for the `emailAddresses` schema used as e-mail override
  objects in sales orders, invoices, and quotations.
  All 3 fields: `bccAddresses`, `ccAddresses`, `toAddresses` (all `list<string>`).
  Helper methods: `getAllAddresses()`, `isEmpty()`.
  Replaces raw `list<array>` for `deliveryEmailAddresses`, `recordEmailAddresses`,
  `salesInvoiceEmailAddresses`, `salesOrderEmailAddresses` fields.

- **`EcommerceOrderDTO`** — typed DTO for the `ecommerceOrder` schema embedded in
  `SalesOrderDTO::$ecommerceOrder`.
  All 6 fields: `amazonFeedSubmissionId`, `amazonInvoiceUploadSuccess`,
  `amazonSalesChannel`, `easyShipped`, `ecommerceId`, `externalConnectionId`.
  Replaces the previous raw `list<array>` storage.

### Changed — Document DTO Completions

- **`SalesOrderDTO`** — added missing `$dispatchCountryCode` field; changed
  `$deliveryEmailAddresses`, `$recordEmailAddresses`, `$salesInvoiceEmailAddresses` from
  `list<array>` to `?EmailAddressesDTO`; changed `$ecommerceOrder` from
  `list<array>` to `?EcommerceOrderDTO`.

- **`SalesInvoiceDTO`** — added missing `$dispatchCountryCode` field; changed
  `$recordEmailAddresses` from `list<array>` to `?EmailAddressesDTO`.

- **`QuotationDTO`** — added 5 missing fields: `$dispatchCountryCode`,
  `$deliveryEmailAddresses`, `$salesInvoiceEmailAddresses`, `$recordEmailAddresses`,
  `$salesOrderEmailAddresses`. All email address fields typed as `?EmailAddressesDTO`.

---

### Added — Party / Customer / Contact / Supplier DTOs (full 137-field schema)

- **`BankAccountDTO`** — typed DTO for `bankAccount` entries embedded in the `$bankAccounts`
  array of all party-based DTOs (PartyDTO, CustomerDTO, ContactDTO, SupplierDTO).
  All 29 fields of the `bankAccount` schema: identity, `accountHolder`, `accountId`,
  `accountNumber`, `active`, `autoSync`, `automaticProcessing`, `balance` (decimal string),
  `bankCode`, `connectionFailure`, `creditInstitute`, `creditInstituteCity/Street/Zip`,
  `creditLine` (decimal string), `currencyId`, `differentSepaCreditorIdentifier`,
  `enabledForElectronicPaymentTransactions`, `iban`, `incidentalCostsOfMonetaryTrafficAccountId`,
  `incidentalCostsOfMonetaryTrafficTaxId`, `lastDownload`, `primary`, `qrIban`, `qrIdentifier`,
  `swiftBic`. Helper methods: `getBalance()`, `getCreditLine()`, `getLastDownloadAt()`.

- **`OnlineAccountDTO`** — typed DTO for `onlineAccount` entries embedded in `$onlineAccounts`.
  All 7 fields: identity, `accountName`, `accountType`, `url`.

- **`PartyHabitualExporterLetterOfIntentDTO`** — typed DTO for habitual exporter letters of intent
  embedded in `$partyHabitualExporterLettersOfIntent`.
  All 12 fields: identity, `automaticallySuggestInInvoice`, `date`, `fromSupplier`, `invoices` (raw),
  `numberDeclarer`, `numberSupplier`, `totalAmount`, `type`.
  Helper methods: `getTotalAmount()`, `getDate()`.

### Changed — Party DTOs Complete Rewrite (137 fields each)

All four party-based DTOs have been rewritten to cover the complete **137-field** `party` schema
(29 from `abstractParty` + 108 party-specific fields). Every endpoint — `/customer`, `/contact`,
`/supplier`, `/party` — returns the full party payload; the DTOs now match it exactly.

Nested arrays are now typed:
- `$addresses` → `list<AddressDTO>`
- `$bankAccounts` → `list<BankAccountDTO>`
- `$onlineAccounts` → `list<OnlineAccountDTO>`
- `$commissionSalesPartners` → `list<CommissionSalesPartnerDTO>`
- `$partyHabitualExporterLettersOfIntent` → `list<PartyHabitualExporterLetterOfIntentDTO>`
- `$customAttributes` → `list<CustomAttributeDTO>`
- `$contacts`, `$partyEmailAddresses`, `$customerSalesStageHistory`, `$tags`, `$topics` → raw `list<array>`

**`PartyDTO`** — rewritten from 10 to 137 fields.
Added new helper methods: `isCustomer()`, `isSupplier()`, `getDisplayName()`,
`getCustomerCreditLimit()`, `getBirthDate()`, `getConvertedOnDate()`.

**`CustomerDTO`** — rewritten from 31 to 137 fields.

**Breaking changes** (field renames to match exact API keys):
- `$mobile` → `$mobilePhone1`
- `$blocked` → `$customerBlocked`
- `$insolvent` → `$customerInsolvent`
- `$salesChannel` → `$customerSalesChannel`
- `$paymentTermId` → `$customerTermOfPaymentId`

**Removed** fields that do not exist in the `party` schema:
`$active`, `$currencyName`, `$deliveryTermId`, `$responsibleUserUsername`, `$vatRegistrationNumber`.

New helper methods: `isBlocked()`, `getCustomerCreditLimit()`, `getBirthDate()`.

**`ContactDTO`** — rewritten from 22 to 137 fields.

**Breaking changes**:
- `$mobile` → `$mobilePhone1`
- `$customerId` → `$parentPartyId` (ID of the parent organisation)

**Removed** fields that do not exist in the `party` schema: `$active`, `$position`, `$department`.
Migration: use `$personRoleId` instead of `$position`; use `$personDepartmentId` instead of `$department`.

**`SupplierDTO`** — rewritten from 25 to 137 fields.

**Breaking changes**:
- `$mobile` → `$mobilePhone1`
- `$blocked` → `$supplierOrderBlock`
- `$active` → `$supplierActive`
- `$paymentTermId` → `$supplierTermOfPaymentId`

**Removed** fields that do not exist in the `party` schema:
`$currencyName`, `$deliveryTermId`, `$vatRegistrationNumber`.

New helper method: `isActive()`, `getSupplierMinimumPurchaseOrderAmount()`, `getDisplayName()`.

---

### Added — Ticket Resource (full 51-field schema)

- **`TicketDTO`** — typed DTO for the `ticket` schema.
  All 51 fields: identity (`id`, `version`, `createdDate`, `lastModifiedDate`), `ticketNumber`,
  `subject`, `description`, `note`, status/classification (`ticketStatusId`, `ticketTypeId`,
  `ticketCategoryId`, `ticketChannelId`, `ticketPriorityId`, `ticketServiceLevelAgreementId`),
  assignment (`assignedUserId`, `assignedPoolingGroupId`, `responsibleUserId`),
  linked entities (`partyId`, `contactId`, `contractId`, `salesOrderId`, `legacyArticleId`,
  `mail2TicketId`), denormalized contact info (`firstName`, `lastName`, `email`,
  `ccEmailAddresses` as comma-separated string, `phoneNumber`, `mobilePhoneNumber`, `room`,
  `language`), billing/performance (`invoicingStatus`, `performanceRecordedStatus`),
  rating (`ticketRating` enum `STARS_1`–`STARS_5`, `ticketRatingComment`, `ticketRatingDate`),
  public page (`publicPageUuid`, `publicPageExpirationDate`), dates (`finishedDate`,
  `followUpDate`, `solutionDueDate`), boolean flags (`billable`, `billableStatus`,
  `disableEmailTemplates`, `isTemplate`, `legacyTimeAndMaterialTicket`, `resolvedYourIssue`).
  Typed nested arrays: `customAttributes` (`list<CustomAttributeDTO>`).
  Raw arrays: `entityReferences` (linked entity `{entityId, entityName}` objects),
  `tags`, `watchers` (`{id}` objects).
  Helper methods: `isBilled()`, `getContactDisplayName()`, `getRatingStars()` (maps enum to
  int 1–5), `getSolutionDueDate()`, `getFinishedDate()`, `getCreatedAt()`, `getLastModifiedAt()`.

- **`TicketResource`** — new resource registered as `$client->tickets()`.
  Methods:
  - `all(?QueryBuilder $query): list<TicketDTO>` — list all tickets
  - `find(string $id): TicketDTO` — fetch by ID
  - `create(array $data): TicketDTO` — create a ticket
  - `update(string $id, array $data): TicketDTO` — update a ticket
  - `delete(string $id): void` — delete a ticket
  - `findByParty(string $partyId): list<TicketDTO>` — filter by linked party
  - `findByStatus(string $ticketStatusId): list<TicketDTO>` — filter by status
  - `findByAssignedUser(string $userId): list<TicketDTO>` — filter by assigned user
  - `findBySalesOrder(string $salesOrderId): list<TicketDTO>` — filter by linked sales order

- **`WeclappClient::tickets()`** — factory method to create a `TicketResource` instance.

---

### Added — Purchase Order Resource (full 68-field schema)

- **`PurchaseOrderItemDTO`** — typed DTO for `purchaseOrderItem` entries embedded in
  `PurchaseOrderDTO::$purchaseOrderItems`.
  Full inheritance chain resolved (42 fields total): identity, `articleId`, `title`, `description`,
  `descriptionFixed`, `quantity`, `unitId`, `unitPrice`, `unitPriceInCompanyCurrency`,
  `discountPercentage`, computed amounts (gross/net incl. company-currency variants),
  fulfillment state (`invoicedQuantity`, `receivedQuantity`), item classification fields,
  manual override flags, service period dates (`servicePeriodFromDate`, `servicePeriodToDate`),
  purchase-specific fields (`articleSupplySourceId`, `blanketPurchaseOrderId`,
  `blanketPurchaseOrderReleaseId`, `purchaseOrderRequestOfferItemId`, `salesOrderItemId`),
  typed `reductionAdditionItems`, raw `batchSerialNumbers`, typed `customAttributes`.
  Helper methods: `getQuantity()`, `getUnitPrice()`, `getNetAmount()`,
  `getServicePeriodFromDate()`, `getServicePeriodToDate()`.

- **`PurchaseOrderDTO`** — typed DTO for the `purchaseOrder` schema.
  All 68 fields: identity, `purchaseOrderNumber`, `status`, `purchaseOrderType`, `supplierId`,
  `creatorId`, `responsibleUserId`, `description`, `note`, `advancePaymentStatus`,
  commercial fields, currency fields, amount fields, reference fields,
  package tracking fields, country codes, dates (orderDate, plannedDeliveryDate,
  plannedShippingDate, servicePeriodFrom/To, shippingNotificationDate),
  boolean flags (disableRecordEmailingRule, includeCashDiscountInValuationPrice,
  invoiced, paid, received, sentToRecipient), record text fields.
  Embedded addresses: `deliveryAddress`, `invoiceAddress`, `recordAddress` (typed `?AddressDTO`).
  Typed nested arrays: `purchaseOrderItems` (`list<PurchaseOrderItemDTO>`),
  `shippingCostItems` (`list<ShippingCostItemDTO>`), `customAttributes` (`list<CustomAttributeDTO>`).
  Typed email object: `recordEmailAddresses` (`?EmailAddressesDTO`).
  Raw arrays: `statusHistory`, `tags`, `dropshippingDeliveryNoteFormTexts`.
  Helper methods: `isFullyReceived()`, `isFullyInvoiced()`, `getNetAmount()`, `getGrossAmount()`,
  `getOrderDate()`, `getPlannedDeliveryDate()`, `getCreatedAt()`, `getLastModifiedAt()`.

- **`PurchaseOrderResource`** — new resource registered as `$client->purchaseOrders()`.
  Methods:
  - `all(?QueryBuilder $query): list<PurchaseOrderDTO>` — list all purchase orders
  - `find(string $id): PurchaseOrderDTO` — fetch by ID
  - `create(array $data): PurchaseOrderDTO` — create a purchase order
  - `update(string $id, array $data): PurchaseOrderDTO` — update a purchase order
  - `delete(string $id): void` — delete a purchase order
  - `findBySupplier(string $supplierId): list<PurchaseOrderDTO>` — filter by supplier
  - `findBySalesOrder(string $salesOrderId): list<PurchaseOrderDTO>` — filter by linked sales order
  - `getPdf(string $id): string` — download the purchase order PDF
  - `getCancellationSlipPdf(string $id): string` — download the cancellation slip PDF

- **`WeclappClient::purchaseOrders()`** — factory method to create a `PurchaseOrderResource` instance.

---

### Added — Shipment Resource (full 61-field schema)

- **`ParcelDTO`** — typed DTO for `parcel` entries embedded in `ShipmentDTO::$parcels`.
  All 23 fields: identity, `declaredValueAmount` (decimal string), `declaredValueCurrencyId`,
  DHL service flags (`dhlGoGreenPlusService`, `dhlPostalDeliveredDutyPaidService`, `dhlPremiumInternationalService`),
  physical dimensions (`height`, `length`, `width` in mm), `weight` (decimal string),
  `positionNumber`, `reference`, `saturdayDelivery`, `shippingCarrierAddition`, `shippingCarrierId`,
  `shippingLabelsCount`, `trackingId`, `trackingUrl`, `useDeliveryDateAsPreferredDeliveryDate`,
  `customAttributes`. Helper methods: `getWeight()`, `getDeclaredValueAmount()`.

- **`ShipmentItemDTO`** — typed DTO for `shipmentItem` entries embedded in `ShipmentDTO::$shipmentItems`.
  All 30 fields: identity, `addPageBreakBefore`, `articleId`, `description`, `descriptionFixed`,
  `groupName`, `itemType`, `manualQuantity`, `note`, `parentItemId`, `positionNumber`,
  `purchaseOrderItemId`, `quantity` (decimal string), `salesOrderItemId`, `title`, `unitId`,
  return-related fields (`returnAssessmentId`, `returnDescription`, `returnErrorId`, `returnReasonId`,
  `returnRectificationId`), typed `picks` (`list<ItemPickDTO>`), raw return reference arrays,
  `customAttributes`. Helper method: `getQuantity()`.

- **`ShipmentDTO`** — typed DTO for the `shipment` schema.
  All 61 fields: identity, `shipmentNumber`, `status`, `shipmentType`, `mainSalesOrderId`,
  `creatorId`, `responsibleUserId`, `description`, delivery info fields, logistics references,
  declared value / customs fields, package tracking fields, weight/dimensions, label counts,
  record text fields, recipient info, boolean flags, dates.
  Embedded addresses: `invoiceAddress`, `recipientAddress`, `shippedFromAddress` (typed `?AddressDTO`).
  Typed nested arrays: `shipmentItems` (`list<ShipmentItemDTO>`), `parcels` (`list<ParcelDTO>`),
  `customAttributes` (`list<CustomAttributeDTO>`).
  Typed email objects: `recordEmailAddresses`, `salesInvoiceEmailAddresses` (`?EmailAddressesDTO`).
  Raw arrays: `purchaseOrders`, `salesOrders`, `statusHistory`, `tags`.
  Helper methods: `isDispatched()`, `getTrackingUrl()`, `getTotalWeight()`, `getPackageWeight()`,
  `getShippingDate()`, `getDeliveryDate()`, `getCreatedAt()`, `getLastModifiedAt()`.

- **`ShipmentResource`** — new resource registered as `$client->shipments()`.
  Methods:
  - `all(?QueryBuilder $query): list<ShipmentDTO>` — list all shipments
  - `find(string $id): ShipmentDTO` — fetch by ID
  - `create(array $data): ShipmentDTO` — create a shipment
  - `update(string $id, array $data): ShipmentDTO` — update a shipment
  - `delete(string $id): void` — delete a shipment
  - `findBySalesOrder(string $salesOrderId): list<ShipmentDTO>` — filter by source order
  - `findByParty(string $partyId): list<ShipmentDTO>` — filter by recipient party
  - `getDeliveryNotePdf(string $id): string` — download delivery note PDF
  - `getPickingListPdf(string $id): string` — download picking list PDF
  - `getShippingLabelPdf(string $id): string` — download shipping label PDF

- **`WeclappClient::shipments()`** — factory method to create a `ShipmentResource` instance.

### Fixed — SalesInvoiceDTO missing field

- **`SalesInvoiceDTO`** — added missing `$description` field (`?string`). The `salesInvoice`
  API schema includes a description field that was omitted from the DTO.

---

### Added — Webhook DTO / Resource Rewrite (correct API schema)

- **`WebhookEntityName` enum** — new string-backed enum listing known weclapp entity names
  for use as the `entityName` argument when registering webhook subscriptions.
  Cases: `Party`, `Article`, `SalesOrder`, `SalesInvoice`, `Quotation`,
  `PurchaseOrder`, `PurchaseInvoice`, `Shipment`, `Contract`, `Ticket`.

### Changed — Webhook DTO / Resource Rewrite (correct API schema)

- **`WebhookDTO`** — completely rewritten to match the 12-field `webhook` schema.

  **Removed** fields that do not exist in the API:
  `$active` (no such field — use `isActive()` helper which checks `$deactivatedDate`),
  `$eventType` (API uses `entityName` + boolean flags, not combined strings),
  `$description` (no such field),
  `$callbackUrl` (wrong name).

  **Added** all correct API fields:
  `$version` (string, readOnly), `$atCreate` (bool), `$atDelete` (bool), `$atUpdate` (bool),
  `$deactivatedDate` (?int, epoch ms), `$entityName` (string), `$errorMessage` (?string),
  `$requestMethod` (string: "GET"|"POST"), `$url` (string).

  New helper methods:
  - `isActive(): bool` — returns `true` when `$deactivatedDate` is `null`
  - `getDeactivatedAt(): ?DateTimeImmutable`

  **Breaking changes**:
  - `$callbackUrl` removed → use `$url`
  - `$active` removed → use `isActive()`
  - `$eventType` removed → use `$entityName` + `$atCreate`/`$atUpdate`/`$atDelete`
  - `$description` removed (no equivalent in the API)
  - `$version` added (required field, now always populated)

- **`WebhookResource::register()`** — signature completely changed to use correct API parameters.

  **Before** (wrong):
  ```php
  register(string $eventType, string $callbackUrl, ?string $description = null): WebhookDTO
  ```
  **After** (correct):
  ```php
  register(
      string $entityName,
      string $url,
      bool   $atCreate      = false,
      bool   $atUpdate      = false,
      bool   $atDelete      = false,
      string $requestMethod = 'POST',
  ): WebhookDTO
  ```
  Validation: throws `InvalidArgumentException` if no event flag is true, or if `$requestMethod`
  is not `"GET"` or `"POST"`.

- **`WebhookEventType` enum** — marked `@deprecated`. The combined `"entity.action"` string values
  (e.g. `"party.created"`) do not exist in the weclapp API. Use `WebhookEntityName` instead
  and pass the boolean flags `atCreate`/`atUpdate`/`atDelete` separately to `register()`.

---

### Added — Article DTOs (full 1:1 API schema coverage)

- **`ArticleImageDTO`** — typed DTO for `articleImage` entries embedded in `ArticleDTO::$articleImages`.
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `fileName`, `mainImage`.

- **`ArticlePriceDTO`** — typed DTO for `articlePriceWithoutArticleReference` entries in `ArticleDTO::$articlePrices`.
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `lastModifiedByUserId`, `price`,
  `currencyId`, `customerId`, `salesChannel`, `priceScaleType`, `priceScaleValue`,
  `description`, `startDate`, `endDate`, `reductionAdditions` (raw).
  Helper methods: `getPrice()`, `getStartDate()`, `getEndDate()`.

- **`ArticleCalculationPriceDTO`** — typed DTO for `articleCalculationPrice` entries in `ArticleDTO::$articleCalculationPrices`.
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `articleCalculationPriceType`,
  `price`, `salesChannel`, `startDate`, `endDate`.
  Helper methods: `getPrice()`, `getStartDate()`, `getEndDate()`.

- **`ArticleAlternativeQuantityDTO`** — typed DTO for alternative warehouse quantity entries in
  `ArticleDTO::$articleAlternativeQuantities`.
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `warehouseId`,
  `minimumOrderQuantity`, `minimumStockQuantity`, `targetStockQuantity`.

- **`CustomerSpecificArticleAttributesDTO`** — typed DTO for customer-article number mappings in
  `ArticleDTO::$customerArticleNumbers`.
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `customerId`, `customerArticleNumber`.

- **`QuantityConversionDTO`** — typed DTO for unit-of-measure conversion entries in
  `ArticleDTO::$quantityConversions`.
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `createdUserId`, `lastEditedUserId`,
  `unitId`, `conversionQuantity`, `oppositeDirection`.
  Helper method: `getConversionQuantity(): ?float`.

- **`SupplySourceDTO`** — typed DTO for procurement source entries in `ArticleDTO::$supplySources`.
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `articleSupplySourceId`, `positionNumber`.

- **`BillOfMaterialItemDTO`** — typed DTO for BOM component entries used in both
  `ArticleDTO::$productionBillOfMaterialItems` and `ArticleDTO::$salesBillOfMaterialItems`.
  Mirrors both the `billOfMaterial` and `salesBillOfMaterialArticleItem` schemas (structurally identical).
  Fields: `id`, `version`, `createdDate`, `lastModifiedDate`, `articleId`, `quantity`, `positionNumber`.
  Helper method: `getQuantity(): ?float`.

### Changed — Article DTO Complete Rewrite

- **`ArticleDTO`** — rewritten from 21 fields to the full **94-field** `article` schema.
  All nested arrays are now typed via dedicated DTOs:
  - `$articleImages` → `list<ArticleImageDTO>`
  - `$articlePrices` → `list<ArticlePriceDTO>`
  - `$articleCalculationPrices` → `list<ArticleCalculationPriceDTO>`
  - `$articleAlternativeQuantities` → `list<ArticleAlternativeQuantityDTO>`
  - `$customerArticleNumbers` → `list<CustomerSpecificArticleAttributesDTO>`
  - `$quantityConversions` → `list<QuantityConversionDTO>`
  - `$supplySources` → `list<SupplySourceDTO>`
  - `$productionBillOfMaterialItems` → `list<BillOfMaterialItemDTO>`
  - `$salesBillOfMaterialItems` → `list<BillOfMaterialItemDTO>`
  - `$customAttributes` → `list<CustomAttributeDTO>`

  **Breaking changes** — the following fields were renamed to match exact API keys:
  - `$descriptionLong` → `$longText` (API key: `longText`)
  - `$unit` → `$unitId` (API key: `unitId`)
  - `$sellable` → `$availableInSale` (API key: `availableInSale`)

  **Removed** fields that do not exist in the `article` schema:
  `$articleCategoryName`, `$purchasable`, `$stockable`, `$salesPrice`, `$purchasePrice`,
  `$availableStock`, `$reservedStock`.

  New helper methods: `getMainImage(): ?ArticleImageDTO`, `isBillOfMaterial(): bool`.

- **`ArticleCategoryDTO`** — rewritten to match the correct 13-field `articleCategory` schema.

  **Removed** fields that do not exist in the `articleCategory` schema:
  `$active`, `$parentCategoryName`.

  **Added** all missing fields: `$description`, `$imageId`, `$articleAccountingCodeId`,
  `$articleCategoryClassificationId`, `$costTypeId`, `$salesCostCenterId`, `$purchaseCostCenterId`.

  Retained helper: `isRootCategory(): bool`.

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
