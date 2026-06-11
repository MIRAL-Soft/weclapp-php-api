<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Query;

use DateTimeInterface;

/**
 * Fluent query builder for weclapp API v2 request parameters.
 *
 * Provides a clean, readable API to compose filter, sort and pagination
 * parameters that are serialised into URL query strings.
 *
 * @example
 * $query = QueryBuilder::new()
 *     ->filterEq('active', true)
 *     ->filterIlike('company', 'acme')
 *     ->modifiedSince(new DateTime('-1 hour'))
 *     ->sort('company')
 *     ->page(1)
 *     ->pageSize(50);
 *
 * // Produces: ?active-eq=1&company-ilike=acme&lastModifiedDate-gt=...&sort=company&page=1&pageSize=50
 *
 * @phpstan-consistent-constructor
 */
class QueryBuilder
{
    /** @var array<int, array{0: string, 1: FilterOperator, 2: mixed}> */
    private array $filters = [];

    /** @var list<string> */
    private array $sorts = [];

    private int $page     = 1;
    private int $pageSize = 50;

    /**
     * Named constructor — creates a new QueryBuilder instance.
     * Preferred over `new QueryBuilder()` for readability in fluent chains.
     */
    public static function new(): static
    {
        return new static();
    }

    /**
     * Add a filter condition.
     *
     * @param string         $field    The API field name (e.g. "company", "lastModifiedDate").
     * @param FilterOperator $operator The comparison operator.
     * @param mixed          $value    The value to compare against.
     *                                 For IN/NOT_IN pass an array.
     *                                 For IS_NULL/NOT_NULL pass null or omit.
     */
    public function filter(string $field, FilterOperator $operator, mixed $value = null): static
    {
        $this->filters[] = [$field, $operator, $value];

        return $this;
    }

    /**
     * Shorthand for FilterOperator::EQ (equals).
     *
     * @param mixed $value Scalar value to match exactly.
     */
    public function filterEq(string $field, mixed $value): static
    {
        return $this->filter($field, FilterOperator::EQ, $value);
    }

    /**
     * Shorthand for FilterOperator::NEQ (not equals).
     */
    public function filterNeq(string $field, mixed $value): static
    {
        return $this->filter($field, FilterOperator::NEQ, $value);
    }

    /**
     * Shorthand for FilterOperator::ILIKE (case-insensitive contains search).
     */
    public function filterIlike(string $field, string $value): static
    {
        return $this->filter($field, FilterOperator::ILIKE, $value);
    }

    /**
     * Shorthand for FilterOperator::GT (greater than).
     */
    public function filterGt(string $field, mixed $value): static
    {
        return $this->filter($field, FilterOperator::GT, $value);
    }

    /**
     * Shorthand for FilterOperator::GTE (greater than or equal).
     */
    public function filterGte(string $field, mixed $value): static
    {
        return $this->filter($field, FilterOperator::GTE, $value);
    }

    /**
     * Shorthand for FilterOperator::LT (less than).
     */
    public function filterLt(string $field, mixed $value): static
    {
        return $this->filter($field, FilterOperator::LT, $value);
    }

    /**
     * Shorthand for FilterOperator::LTE (less than or equal).
     */
    public function filterLte(string $field, mixed $value): static
    {
        return $this->filter($field, FilterOperator::LTE, $value);
    }

    /**
     * Shorthand for FilterOperator::IN (value is in list).
     *
     * @param list<mixed> $values
     */
    public function filterIn(string $field, array $values): static
    {
        return $this->filter($field, FilterOperator::IN, $values);
    }

    /**
     * Filter to only include records modified after the given point in time.
     *
     * This is the recommended way to implement delta/incremental sync:
     * store the timestamp of your last sync run and pass it here on the next run.
     *
     * @param DateTimeInterface|int $since A DateTime object or Unix epoch in milliseconds.
     *
     * @example
     * // Fetch only customers changed in the last 24 hours
     * $query->modifiedSince(new DateTime('-24 hours'));
     *
     * // Using a stored epoch timestamp (milliseconds)
     * $query->modifiedSince($lastSyncEpochMs);
     */
    public function modifiedSince(DateTimeInterface|int $since): static
    {
        $epochMs = $this->toEpochMs($since);

        return $this->filter('lastModifiedDate', FilterOperator::GT, $epochMs);
    }

    /**
     * Filter to only include records created after the given point in time.
     *
     * @param DateTimeInterface|int $since A DateTime object or Unix epoch in milliseconds.
     */
    public function createdSince(DateTimeInterface|int $since): static
    {
        $epochMs = $this->toEpochMs($since);

        return $this->filter('createdDate', FilterOperator::GT, $epochMs);
    }

    /**
     * Add a sort field.
     *
     * Multiple sort fields can be chained; they are applied in order.
     *
     * @param string $field     The API field name to sort by.
     * @param string $direction "asc" (default) or "desc".
     */
    public function sort(string $field, string $direction = 'asc'): static
    {
        $this->sorts[] = ($direction === 'desc' ? '-' : '') . $field;

        return $this;
    }

    /**
     * Sort by last modification date.
     *
     * @param string $direction "asc" (oldest first) or "desc" (newest first).
     */
    public function sortByModified(string $direction = 'asc'): static
    {
        return $this->sort('lastModifiedDate', $direction);
    }

    /**
     * Sort by creation date.
     *
     * @param string $direction "asc" (oldest first) or "desc" (newest first).
     */
    public function sortByCreated(string $direction = 'asc'): static
    {
        return $this->sort('createdDate', $direction);
    }

    /**
     * Set the page number for pagination (1-based).
     */
    public function page(int $page): static
    {
        $this->page = max(1, $page);

        return $this;
    }

    /**
     * Set the number of results per page.
     *
     * The weclapp API supports a maximum of 1000 records per request.
     */
    public function pageSize(int $size): static
    {
        $this->pageSize = max(1, min(1000, $size));

        return $this;
    }

    /**
     * Returns the current page number.
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Returns the current page size.
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Serialise all filters, sorts and pagination into a URL query string.
     *
     * Includes page and pageSize parameters.
     */
    public function build(): string
    {
        $params = $this->buildFilterParams();

        if (!empty($this->sorts)) {
            $params['sort'] = implode(',', $this->sorts);
        }

        $params['page']     = $this->page;
        $params['pageSize'] = $this->pageSize;

        return '?' . http_build_query($params);
    }

    /**
     * Serialise only the filter parameters (no pagination).
     *
     * Used for count() requests where pagination is irrelevant.
     */
    public function buildForCount(): string
    {
        $params = $this->buildFilterParams();

        return empty($params) ? '' : ('?' . http_build_query($params));
    }

    /**
     * Build an array of filter key-value pairs from the registered filters.
     *
     * @return array<string, mixed>
     */
    private function buildFilterParams(): array
    {
        $params = [];

        foreach ($this->filters as [$field, $operator, $value]) {
            $key = $field . $operator->value;

            if ($operator->isUnary()) {
                // IS NULL / NOT NULL — no value needed
                $params[$key] = 'true';
            } elseif ($operator->isList()) {
                // IN / NOT IN — comma-separated list
                $params[$key] = implode(',', array_map(
                    static fn (mixed $v): string => (string) $v,
                    (array) $value
                ));
            } elseif ($value instanceof \BackedEnum) {
                $params[$key] = $value->value;
            } elseif (is_bool($value)) {
                $params[$key] = $value ? 'true' : 'false';
            } else {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * Convert a DateTimeInterface or epoch-millisecond integer to epoch milliseconds.
     *
     * For DateTimeInterface objects the millisecond component is preserved:
     *   - `format('U')` returns the whole seconds as a string (exact integer, no float)
     *   - `format('v')` returns the milliseconds 000–999 (PHP 7.1+)
     *
     * This is important for ms-precise delta-sync watermarks: passing the weclapp
     * `lastModifiedDate` value (+1 ms) as a `DateTimeImmutable` must round-trip
     * without loss, otherwise the last processed record re-appears in the next scan.
     *
     * `getTimestamp() * 1000` was intentionally avoided: `getTimestamp()` always
     * truncates to whole seconds, silently discarding any sub-second component.
     *
     * @param DateTimeInterface|int $value
     */
    private function toEpochMs(DateTimeInterface|int $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return (int) $value->format('U') * 1000 + (int) $value->format('v');
        }

        return $value;
    }
}
