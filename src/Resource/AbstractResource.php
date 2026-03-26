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
     * @param HttpClient        $http        HTTP client for making requests.
     * @param RateLimiter       $rateLimiter Handles HTTP 429 retry logic.
     * @param CacheInterface|null $cache     Optional PSR-16 cache for listAll() results.
     */
    public function __construct(
        protected readonly HttpClient    $http,
        protected readonly RateLimiter   $rateLimiter,
        protected readonly ?CacheInterface $cache = null,
    ) {}

    /**
     * Returns the total number of records matching the optional filter query.
     *
     * @param QueryBuilder|null $query Optional filters (no pagination/sort needed).
     *
     * @throws WeclappApiException
     */
    public function count(?QueryBuilder $query = null): int
    {
        $queryString = ($query ?? QueryBuilder::new())->buildForCount();

        $data = $this->rateLimiter->execute(
            fn () => $this->http->get($this->endpoint . '/count', $queryString)
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
            fn () => $this->http->get($this->endpoint . '/' . $id)
        );

        return ($this->dtoClass)::fromArray($data);
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
        $q    = $query ?? QueryBuilder::new();
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
        $pageSize = 100; // Larger pages for efficiency

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
            fn () => $this->http->post($this->endpoint, $data)
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
            fn () => $this->http->put($this->endpoint . '/' . $id, $data)
        );

        return ($this->dtoClass)::fromArray($response);
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
            fn () => $this->http->delete($this->endpoint . '/' . $id)
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
     * Build a PSR-16 cache key for the given operation and query.
     *
     * @param string            $operation The operation name (e.g. "listAll").
     * @param QueryBuilder|null $query     The query builder (used for cache busting on different filters).
     */
    protected function buildCacheKey(string $operation, ?QueryBuilder $query): string
    {
        $queryHash = $query !== null ? md5($query->build()) : 'default';

        return sprintf('weclapp.%s.%s.%s', $this->endpoint, $operation, $queryHash);
    }
}
