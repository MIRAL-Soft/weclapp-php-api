<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use DateTimeInterface;
use miralsoft\weclapp\api\Client\HttpClient;
use miralsoft\weclapp\api\Client\RateLimiter;
use miralsoft\weclapp\api\DTO\AbstractDTO;
use miralsoft\weclapp\api\DTO\PaginatedResultDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Util\ResponseParser;
use Psr\SimpleCache\CacheInterface;

/**
 * Abstract base class for all weclapp API resource classes.
 *
 * Provides complete CRUD operations (count, find, list, listAll,
 * create, update, delete) and delta-sync helpers (findModifiedSince,
 * findCreatedSince). All operations go through the RateLimiter for
 * automatic HTTP 429 retry handling.
 *
 * Concrete subclasses must define:
 *   - $endpoint:  The API path segment (e.g. "customer", "article").
 *   - $dtoClass:  The fully qualified DTO class name (e.g. CustomerDTO::class).
 *
 * @template T of AbstractDTO
 */
abstract class AbstractResource
{
    /**
     * The API endpoint path segment.
     *
     * @example "customer", "article", "salesOrder"
     */
    protected string $endpoint;

    /**
     * Fully qualified class name of the DTO to hydrate responses into.
     *
     * @example \miralsoft\weclapp\api\DTO\CustomerDTO::class
     */
    protected string $dtoClass;

    /**
     * Whether write operations (create / update / delete) are sent with ?dryRun=true.
     *
     * Never mutate this directly — use withDryRun() which returns a clone.
     */
    private bool $dryRun = false;

    /**
     * @param HttpClient        $http        HTTP client for making requests.
     * @param RateLimiter       $rateLimiter Handles HTTP 429 retry logic.
     * @param CacheInterface|null $cache     Optional PSR-16 cache for listAll() results.
     */
    public function __construct(
        protected readonly HttpClient    $http,
        protected readonly RateLimiter   $rateLimiter,
        protected readonly ?CacheInterface $cache = null,
    ) {}

    // -------------------------------------------------------------------------
    // Dry-run mode
    // -------------------------------------------------------------------------

    /**
     * Return a copy of this resource with dry-run mode enabled (or disabled).
     *
     * In dry-run mode every write operation (create, update, delete) appends
     * `?dryRun=true` to the request URL. weclapp then validates the payload and
     * executes business logic — but **does not persist any data**.
     *
     * Response behaviour in dry-run mode:
     * - **create / update**: HTTP 200 with the entity body minus the four meta
     *   fields (`id`, `version`, `createdDate`, `lastModifiedDate`). Those fields
     *   will therefore be `null` in the returned DTO — all other fields are
     *   populated as they would be after a real write.
     * - **delete**: HTTP 200 (instead of 204); the body is silently ignored and
     *   `void` is returned, as with a regular delete.
     * - **errors**: identical to a real call — `ValidationException` on 400,
     *   `NotFoundException` on 404, `OptimisticLockException` on 409, etc.
     *
     * The original resource instance is never mutated; a lightweight clone is
     * returned. Call `withDryRun(false)` to get a non-dry-run clone.
     *
     * **Supported endpoints:** generic `POST`, `PUT`, `DELETE`. Read operations
     * (`find`, `list`, `listAll`, etc.) are unaffected and always hit the real API.
     *
     * @example Validate a new order without persisting it
     * ```php
     * $order = $client->salesOrders()->withDryRun()->create([
     *     'customerId' => 'cust-123',
     *     'orderItems' => [['articleId' => 'art-1', 'quantity' => '2.00']],
     * ]);
     * // $order->id === null (not saved), $order->status is populated
     * ```
     *
     * @example Test whether an update would pass validation
     * ```php
     * try {
     *     $client->customers()->withDryRun()->update($id, ['vatId' => 'INVALID']);
     *     echo 'Update would succeed.';
     * } catch (ValidationException $e) {
     *     echo 'Would fail: ' . implode(', ', $e->getErrors());
     * }
     * ```
     *
     * @example Verify a delete would not be rejected (e.g. referenced record)
     * ```php
     * try {
     *     $client->articles()->withDryRun()->delete($articleId);
     *     echo 'Safe to delete.';
     * } catch (WeclappApiException $e) {
     *     echo 'Cannot delete: ' . $e->getMessage();
     * }
     * ```
     */
    public function withDryRun(bool $enable = true): static
    {
        $clone          = clone $this;
        $clone->dryRun  = $enable;

        return $clone;
    }

    /**
     * Returns true if this resource instance is operating in dry-run mode.
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Returns the total number of records matching the optional filter query.
     *
     * @param QueryBuilder|null $query Optional filters (no pagination/sort needed).
     *
     * @throws WeclappApiException
     */
    public function count(?QueryBuilder $query = null): int
    {
        $q = $this->applyDefaultFilters(clone ($query ?? QueryBuilder::new()));

        $data = $this->rateLimiter->execute(
            fn () => $this->http->get($this->endpoint . '/count', $q->buildForCount())
        );

        return ResponseParser::extractTotalCount($data) ?? 0;
    }

    /**
     * Retrieve a single record by its weclapp ID.
     *
     * @param string $id The weclapp UUID.
     * @return T
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException If the record does not exist.
     * @throws WeclappApiException
     */
    public function find(string $id): AbstractDTO
    {
        $data = $this->rateLimiter->execute(
            fn () => $this->http->get($this->endpoint . '/id/' . $id)
        );

        return ($this->dtoClass)::fromArray($data);
    }

    /**
     * Fetch the complete raw API response for a single record as a plain array.
     *
     * Unlike find(), this skips DTO mapping and returns every field the API
     * returns — including read-only system fields (e.g. `statusHistory`,
     * `shipped`, `currencyConversionDate`) that are not mapped by the DTO.
     *
     * **Primary use cases:**
     *
     * 1. **Backup before a write operation** — save the complete record state so
     *    that any unintended change can be detected or reversed:
     *    ```php
     *    $backup = $client->articles()->findRaw($id); // complete snapshot
     *    $client->articles()->patch($id, ['name' => $newName]);
     *    // ... if something went wrong, restore:
     *    $current = $client->articles()->findRaw($id);
     *    $restore = array_merge($backup, ['version' => $current['version']]);
     *    $client->articles()->update($id, $restore);
     *    ```
     *    ⚠️ **Optimistic locking:** The `version` field in the snapshot reflects
     *    the state at backup time. If the record was modified between the backup
     *    and the restore call, weclapp rejects the PUT with HTTP 409
     *    (`OptimisticLockException`). Always merge the current `version` into
     *    the backup before calling `update()`, as shown above.
     *
     * 2. **Read-Modify-Write** — used internally by `patch()`,
     *    `SalesOrderResource::addOrderItem()`, `updateOrderItem()`, and
     *    `removeOrderItem()` to ensure the full payload (including all read-only
     *    fields) is always sent back, preventing weclapp's "field absent = reset
     *    to null" behaviour from silently clearing unrelated data.
     *
     * The returned array can be passed directly to `update($id, $raw)` and
     * weclapp will accept it without validation errors, provided the `version`
     * matches the current record version.
     *
     * @param string $id The weclapp UUID of the record to fetch.
     * @return array<string, mixed> Complete raw API response body.
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException If the record does not exist.
     * @throws WeclappApiException
     */
    public function findRaw(string $id): array
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->get($this->endpoint . '/id/' . $id)
        );
    }

    /**
     * Retrieve a paginated list of records.
     *
     * @param QueryBuilder|null $query Filters, sort order and pagination parameters.
     * @return PaginatedResultDTO<T>
     *
     * @throws WeclappApiException
     */
    public function list(?QueryBuilder $query = null): PaginatedResultDTO
    {
        $q    = $this->applyDefaultFilters(clone ($query ?? QueryBuilder::new()));
        $data = $this->rateLimiter->execute(
            fn () => $this->http->get($this->endpoint, $q->build())
        );

        $rawItems = ResponseParser::extractList($data);
        $items    = array_map(
            fn (array $item): AbstractDTO => ($this->dtoClass)::fromArray($item),
            $rawItems
        );

        $total   = ResponseParser::extractTotalCount($data) ?? count($items);
        $hasMore = count($items) >= $q->getPageSize();

        return new PaginatedResultDTO(
            items:    $items,
            total:    $total,
            page:     $q->getPage(),
            pageSize: $q->getPageSize(),
            hasMore:  $hasMore,
        );
    }

    /**
     * Retrieve ALL records matching the query, automatically paginating through all pages.
     *
     * Results are cached in PSR-16 if a cache was injected.
     * Cache TTL is 5 minutes by default; override cacheKey() for custom control.
     *
     * WARNING: For large datasets this can result in many API requests.
     * Prefer list() with explicit pagination for better control.
     *
     * @param QueryBuilder|null $query Optional filters and sort parameters.
     *                                 Page and pageSize are managed internally.
     * @return list<T>
     *
     * @throws WeclappApiException
     */
    public function listAll(?QueryBuilder $query = null): array
    {
        $cacheKey = $this->buildCacheKey('listAll', $query);

        if ($this->cache !== null && $this->cache->has($cacheKey)) {
            /** @var list<T> */
            return $this->cache->get($cacheKey);
        }

        $all      = [];
        $page     = 1;
        $pageSize = 1000; // Maximum allowed by weclapp API for efficiency

        // Clone the query to avoid mutating the caller's instance
        $q = clone ($query ?? QueryBuilder::new());
        $q->pageSize($pageSize);

        do {
            $q->page($page);
            $result = $this->list($q);
            $all    = array_merge($all, $result->items);
            $page++;
        } while ($result->hasMore);

        if ($this->cache !== null) {
            $this->cache->set($cacheKey, $all, 300); // 5-minute TTL
        }

        return $all;
    }

    /**
     * Create a new record via the API.
     *
     * @param array<string, mixed> $data The record data to create.
     * @return T
     *
     * @throws \miralsoft\weclapp\api\Exception\ValidationException If data is invalid.
     * @throws WeclappApiException
     */
    public function create(array $data): AbstractDTO
    {
        $response = $this->rateLimiter->execute(
            fn () => $this->http->post($this->endpoint, $data, $this->dryRun)
        );

        return ($this->dtoClass)::fromArray($response);
    }

    /**
     * Update an existing record via the API.
     *
     * Note: weclapp uses optimistic locking via the "version" field.
     * Include the current version in $data to avoid conflicts.
     *
     * @param string               $id   The weclapp UUID of the record to update.
     * @param array<string, mixed> $data The fields to update (partial update supported).
     * @return T
     *
     * @throws \miralsoft\weclapp\api\Exception\ValidationException If data is invalid.
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException   If the record does not exist.
     * @throws WeclappApiException
     */
    public function update(string $id, array $data): AbstractDTO
    {
        $response = $this->rateLimiter->execute(
            fn () => $this->http->put($this->endpoint . '/id/' . $id, $data, $this->dryRun)
        );

        return ($this->dtoClass)::fromArray($response);
    }

    /**
     * Safely update one or more fields on an existing record (Read-Modify-Write).
     *
     * Unlike update(), which sends whatever you provide and lets weclapp treat
     * absent fields as "reset to null/default", patch() first fetches the
     * complete raw record from the API — including all read-only system fields
     * such as `statusHistory`, `shipped`, `currencyConversionDate`, etc. — then
     * merges your changes on top and submits the full record back via PUT.
     *
     * This means **all** existing field values are preserved; only the keys you
     * explicitly provide in `$fields` are changed.
     *
     * **Optimistic locking** is handled automatically: the `version` obtained
     * from the GET response is always used. If another process modifies the
     * record between the GET and the PUT, weclapp returns HTTP 409 and an
     * `OptimisticLockException` is thrown — the caller must re-fetch and retry.
     *
     * `id` and `version` in `$fields` are silently stripped: they are always
     * taken from the live GET response to prevent accidental locking violations.
     *
     * **Dry-run compatible** — works transparently with `withDryRun()`: the GET
     * is always real (to obtain the current state), the PUT is sent with
     * `?dryRun=true` (validated but not persisted).
     *
     * @param string               $id     The weclapp UUID of the record to update.
     * @param array<string, mixed> $fields The fields to change. At least one required.
     * @return T
     *
     * @throws \InvalidArgumentException  If $fields is empty.
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException   If the record does not exist.
     * @throws \miralsoft\weclapp\api\Exception\OptimisticLockException If concurrently modified.
     * @throws WeclappApiException
     *
     * @example Change only the name of an article
     * ```php
     * $article = $client->articles()->patch($id, ['name' => 'Corrected Name']);
     * ```
     *
     * @example Rename a customer without losing any other field
     * ```php
     * $customer = $client->customers()->patch($id, ['company' => 'New GmbH']);
     * ```
     *
     * @example Dry-run: validate the patch without persisting
     * ```php
     * $preview = $client->articles()->withDryRun()->patch($id, ['name' => 'Preview Name']);
     * // $preview->id === '' (not saved), but all computed fields are populated
     * ```
     */
    public function patch(string $id, array $fields): AbstractDTO
    {
        if (empty($fields)) {
            throw new \InvalidArgumentException(
                'patch() requires at least one field to update. Pass a non-empty $fields array.'
            );
        }

        $raw = $this->findRaw($id);

        // id and version must always come from the GET response to honour
        // optimistic locking. Strip them from $fields so the caller cannot
        // accidentally supply a stale version and bypass the concurrency check.
        unset($fields['id'], $fields['version']);

        return $this->update($id, array_merge($raw, $fields));
    }

    /**
     * Delete a record by its weclapp ID.
     *
     * @param string $id The weclapp UUID of the record to delete.
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException If the record does not exist.
     * @throws WeclappApiException
     */
    public function delete(string $id): void
    {
        $this->rateLimiter->execute(
            fn () => $this->http->delete($this->endpoint . '/id/' . $id, $this->dryRun)
        );
    }

    /**
     * Retrieve all records modified after the given point in time.
     *
     * This is the recommended approach for delta/incremental sync with external
     * systems. Store the epoch millisecond timestamp of your last sync run and
     * pass it here on the next run to fetch only changed records.
     *
     * @param DateTimeInterface|int $since  A DateTime object or epoch milliseconds.
     * @param QueryBuilder|null     $extra  Additional filters or sort order.
     * @return list<T>
     *
     * @example
     * // First run: fetch all, store last timestamp
     * $customers   = $client->customers()->listAll();
     * $lastSyncMs  = time() * 1000;
     *
     * // Subsequent runs: fetch only changed records
     * $changed = $client->customers()->findModifiedSince($lastSyncMs);
     * foreach ($changed as $customer) {
     *     $lastSyncMs = max($lastSyncMs, $customer->lastModifiedDate);
     *     $externalSystem->update($customer);
     * }
     *
     * @throws WeclappApiException
     */
    public function findModifiedSince(DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        $q = clone ($extra ?? QueryBuilder::new());
        $q->modifiedSince($since)->sortByModified('asc');

        return $this->listAll($q);
    }

    /**
     * Retrieve all records created after the given point in time.
     *
     * @param DateTimeInterface|int $since  A DateTime object or epoch milliseconds.
     * @param QueryBuilder|null     $extra  Additional filters or sort order.
     * @return list<T>
     *
     * @throws WeclappApiException
     */
    public function findCreatedSince(DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        $q = clone ($extra ?? QueryBuilder::new());
        $q->createdSince($since)->sortByCreated('asc');

        return $this->listAll($q);
    }

    /**
     * Invalidate the PSR-16 cache for this resource's listAll() results.
     *
     * Pass the same QueryBuilder you used in listAll() to clear that specific
     * cached result. Pass null (default) to clear the unfiltered listAll() cache.
     *
     * @param QueryBuilder|null $query The query whose cache entry should be removed.
     *                                 Defaults to null (clears the no-filter cache).
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @example
     * // Clear the unfiltered listAll() cache:
     * $client->articles()->clearCache();
     *
     * // Clear a filtered cache:
     * $query = QueryBuilder::new()->filterEq('active', true);
     * $client->articles()->clearCache($query);
     */
    public function clearCache(?QueryBuilder $query = null): void
    {
        if ($this->cache !== null) {
            $this->cache->delete($this->buildCacheKey('listAll', $query));
        }
    }

    /**
     * Stream all matching records as a memory-efficient generator.
     *
     * Unlike listAll() which loads all pages into memory, cursor() yields
     * DTOs one by one while paginating lazily. Ideal for large datasets.
     *
     * @param QueryBuilder|null $query Optional filters and sort parameters.
     * @return \Generator<int, T>
     *
     * @throws WeclappApiException
     *
     * @example
     * foreach ($client->customers()->cursor() as $customer) {
     *     $crm->sync($customer);
     * }
     */
    public function cursor(?QueryBuilder $query = null): \Generator
    {
        $page = 1;
        $q    = clone ($query ?? QueryBuilder::new()->pageSize(100));

        do {
            $q->page($page);
            $result = $this->list($q);

            foreach ($result->items as $item) {
                yield $item;
            }

            $page++;
        } while ($result->hasMore);
    }

    /**
     * Inject default filters for this resource type.
     *
     * Subclasses can override this to scope all read operations to a specific
     * subset of records. The base implementation is a no-op.
     *
     * Called automatically by count(), list() and cursor() — always receives
     * a fresh clone so the original caller's QueryBuilder is never mutated.
     *
     * @example In CustomerResource:
     *   protected function applyDefaultFilters(QueryBuilder $query): QueryBuilder
     *   {
     *       return $query->filter('customerNumber', FilterOperator::NOT_NULL);
     *   }
     */
    protected function applyDefaultFilters(QueryBuilder $query): QueryBuilder
    {
        return $query;
    }

    /**
     * Build a PSR-16 cache key for the given operation and query.
     *
     * Uses the concrete class name (via static::class) so that different
     * resource types sharing the same API endpoint (e.g. CustomerResource
     * and SupplierResource both on /party) never produce colliding cache keys.
     *
     * @param string            $operation The operation name (e.g. "listAll").
     * @param QueryBuilder|null $query     The query builder (used for cache busting on different filters).
     */
    protected function buildCacheKey(string $operation, ?QueryBuilder $query): string
    {
        $queryHash    = $query !== null ? md5($query->build()) : 'default';
        $resourceName = substr(strrchr(static::class, '\\') ?: static::class, 1);

        return sprintf('weclapp.%s.%s.%s', $resourceName, $operation, $queryHash);
    }
}
