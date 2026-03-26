<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Wraps a paginated list response from the weclapp API.
 *
 * Returned by all Resource::list() methods and carries both the
 * current page of items and metadata needed for further pagination.
 *
 * @template T of AbstractDTO
 *
 * @example
 * $result = $client->customers()->list(QueryBuilder::new()->page(1)->pageSize(50));
 *
 * foreach ($result->items as $customer) {
 *     echo $customer->company;
 * }
 *
 * if ($result->hasMore) {
 *     $nextPage = $client->customers()->list(QueryBuilder::new()->page(2)->pageSize(50));
 * }
 */
final class PaginatedResultDTO
{
    /**
     * @param list<AbstractDTO> $items    The DTOs for the current page.
     * @param int               $total    Total number of matching records across all pages.
     *                                    May be -1 if the API did not return a total count.
     * @param int               $page     The 1-based current page number.
     * @param int               $pageSize The number of items requested per page.
     * @param bool              $hasMore  True if there are more pages after this one.
     */
    public function __construct(
        public readonly array $items,
        public readonly int   $total,
        public readonly int   $page,
        public readonly int   $pageSize,
        public readonly bool  $hasMore,
    ) {}

    /**
     * Returns the number of items in the current page.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Returns true if the current page is the first page.
     */
    public function isFirstPage(): bool
    {
        return $this->page === 1;
    }

    /**
     * Returns the next page number, or null if this is the last page.
     */
    public function nextPage(): ?int
    {
        return $this->hasMore ? $this->page + 1 : null;
    }
}
