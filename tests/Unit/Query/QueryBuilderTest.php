<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Query;

use miralsoft\weclapp\api\Query\FilterOperator;
use miralsoft\weclapp\api\Query\QueryBuilder;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QueryBuilder.
 */
class QueryBuilderTest extends TestCase
{
    public function test_builds_empty_query_with_defaults(): void
    {
        $query = QueryBuilder::new()->build();

        self::assertStringContainsString('page=1', $query);
        self::assertStringContainsString('pageSize=50', $query);
    }

    public function test_builds_eq_filter(): void
    {
        $query = QueryBuilder::new()
            ->filterEq('active', true)
            ->build();

        self::assertStringContainsString('active-eq=true', $query);
    }

    public function test_builds_ilike_filter(): void
    {
        $query = QueryBuilder::new()
            ->filterIlike('company', 'acme')
            ->build();

        self::assertStringContainsString('company-ilike=acme', $query);
    }

    public function test_builds_gt_filter(): void
    {
        $query = QueryBuilder::new()
            ->filterGt('createdDate', 1711400000000)
            ->build();

        self::assertStringContainsString('createdDate-gt=1711400000000', $query);
    }

    public function test_modified_since_with_datetime(): void
    {
        $dt    = new DateTime('2024-01-01 00:00:00');
        $query = QueryBuilder::new()->modifiedSince($dt)->build();

        $expectedMs = $dt->getTimestamp() * 1000;
        self::assertStringContainsString('lastModifiedDate-gt=' . $expectedMs, $query);
    }

    public function test_modified_since_with_epoch_ms(): void
    {
        $epochMs = 1711400000000;
        $query   = QueryBuilder::new()->modifiedSince($epochMs)->build();

        self::assertStringContainsString('lastModifiedDate-gt=' . $epochMs, $query);
    }

    public function test_builds_sort_asc(): void
    {
        $query = QueryBuilder::new()->sort('company')->build();

        self::assertStringContainsString('sort=company', $query);
    }

    public function test_builds_sort_desc(): void
    {
        $query = QueryBuilder::new()->sort('company', 'desc')->build();

        self::assertStringContainsString('sort=-company', $query);
    }

    public function test_builds_multiple_sorts(): void
    {
        $query = QueryBuilder::new()
            ->sort('company')
            ->sort('customerNumber', 'desc')
            ->build();

        self::assertStringContainsString('sort=company%2C-customerNumber', $query);
    }

    public function test_sets_page_and_page_size(): void
    {
        $query = QueryBuilder::new()
            ->page(3)
            ->pageSize(100)
            ->build();

        self::assertStringContainsString('page=3', $query);
        self::assertStringContainsString('pageSize=100', $query);
    }

    public function test_page_minimum_is_one(): void
    {
        $q = QueryBuilder::new()->page(0);

        self::assertSame(1, $q->getPage());
    }

    public function test_page_size_is_capped_at_1000(): void
    {
        $q = QueryBuilder::new()->pageSize(9999);

        self::assertSame(1000, $q->getPageSize());
    }

    public function test_builds_in_filter(): void
    {
        $query = QueryBuilder::new()
            ->filterIn('status', ['OPEN', 'CONFIRMED'])
            ->build();

        self::assertStringContainsString('status-in=OPEN%2CCONFIRMED', $query);
    }

    public function test_build_for_count_excludes_pagination(): void
    {
        $queryString = QueryBuilder::new()
            ->filterEq('active', true)
            ->buildForCount();

        self::assertStringContainsString('active-eq=true', $queryString);
        self::assertStringNotContainsString('page', $queryString);
        self::assertStringNotContainsString('pageSize', $queryString);
    }

    public function test_build_for_count_returns_empty_string_with_no_filters(): void
    {
        $queryString = QueryBuilder::new()->buildForCount();

        self::assertSame('', $queryString);
    }

    public function test_fluent_interface_returns_same_instance(): void
    {
        $builder = QueryBuilder::new();
        $result  = $builder->filterEq('active', true);

        self::assertSame($builder, $result);
    }
}
