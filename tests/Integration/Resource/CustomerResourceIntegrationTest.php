<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\CustomerDTO;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for CustomerResource.
 *
 * All tests are read-only — no data is created or modified.
 */
class CustomerResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_paginated_result(): void
    {
        $result = $this->client()->customers()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertGreaterThanOrEqual(0, $result->total);
        self::assertContainsOnlyInstancesOf(CustomerDTO::class, $result->items);
    }

    public function test_first_customer_has_required_fields(): void
    {
        $result = $this->client()->customers()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No customers in this tenant.');
        }

        $customer = $result->items[0];

        self::assertNotEmpty($customer->id);
        self::assertIsInt($customer->createdDate);
        self::assertIsInt($customer->lastModifiedDate);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->customers()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No customers in this tenant.');
        }

        $id       = $result->items[0]->id;
        $customer = $this->client()->customers()->find($id);

        self::assertInstanceOf(CustomerDTO::class, $customer);
        self::assertSame($id, $customer->id);
    }

    public function test_count_matches_list_total(): void
    {
        $query  = QueryBuilder::new()->pageSize(1);
        $result = $this->client()->customers()->list($query);
        $count  = $this->client()->customers()->count($query);

        self::assertSame($result->total, $count);
    }

    public function test_modified_since_returns_valid_dtos(): void
    {
        $result = $this->client()->customers()->list(
            QueryBuilder::new()
                ->modifiedSince(new \DateTime('-1 year'))
                ->pageSize(10),
        );

        self::assertContainsOnlyInstancesOf(CustomerDTO::class, $result->items);
    }
}
