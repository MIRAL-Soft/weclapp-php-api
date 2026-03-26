<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\CustomerDTO;
use miralsoft\weclapp\api\DTO\PaginatedResultDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaginatedResultDTO.
 */
class PaginatedResultDTOTest extends TestCase
{
    private function makeCustomer(string $id): CustomerDTO
    {
        return CustomerDTO::fromArray([
            'id'             => $id,
            'customerNumber' => 'K-' . $id,
            'createdDate'    => 0,
            'lastModifiedDate' => 0,
        ]);
    }

    public function test_count_returns_number_of_items(): void
    {
        $result = new PaginatedResultDTO(
            items:    [$this->makeCustomer('1'), $this->makeCustomer('2')],
            total:    100,
            page:     1,
            pageSize: 50,
            hasMore:  true,
        );

        self::assertSame(2, $result->count());
    }

    public function test_is_first_page(): void
    {
        $result = new PaginatedResultDTO([], 0, 1, 50, false);

        self::assertTrue($result->isFirstPage());
    }

    public function test_is_not_first_page(): void
    {
        $result = new PaginatedResultDTO([], 0, 2, 50, false);

        self::assertFalse($result->isFirstPage());
    }

    public function test_next_page_returns_null_when_no_more(): void
    {
        $result = new PaginatedResultDTO([], 0, 3, 50, false);

        self::assertNull($result->nextPage());
    }

    public function test_next_page_returns_incremented_page(): void
    {
        $result = new PaginatedResultDTO([], 0, 3, 50, true);

        self::assertSame(4, $result->nextPage());
    }

    public function test_has_more_is_true_when_items_fill_page(): void
    {
        $result = new PaginatedResultDTO([], 500, 1, 50, true);

        self::assertTrue($result->hasMore);
    }
}
