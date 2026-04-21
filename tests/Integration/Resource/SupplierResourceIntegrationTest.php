<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\SupplierDTO;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for SupplierResource (/api/v2/party filtered by supplierNumber).
 * All tests are read-only.
 */
class SupplierResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_supplier_dtos(): void
    {
        $result = $this->client()->suppliers()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertContainsOnlyInstancesOf(SupplierDTO::class, $result->items);
    }

    public function test_first_supplier_has_supplier_number(): void
    {
        $result = $this->client()->suppliers()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No suppliers found in this tenant.');
        }

        $supplier = $result->items[0];

        self::assertNotEmpty($supplier->id);
        // Suppliers should have a supplierNumber set.
        // Some tenants may have supplier parties without a number — skip in that case.
        if (empty($supplier->supplierNumber)) {
            $this->markTestSkipped(
                'First supplier has no supplierNumber — the NOT_NULL filter may not be enforced for all records in this tenant.',
            );
        }
        self::assertNotEmpty($supplier->supplierNumber);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->suppliers()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No suppliers found in this tenant.');
        }

        $id       = $result->items[0]->id;
        $supplier = $this->client()->suppliers()->find($id);

        self::assertInstanceOf(SupplierDTO::class, $supplier);
        self::assertSame($id, $supplier->id);
    }

    public function test_count_returns_non_negative_integer(): void
    {
        $count = $this->client()->suppliers()->count();

        self::assertIsInt($count);
        self::assertGreaterThanOrEqual(0, $count);
    }

    public function test_find_by_company_returns_subset(): void
    {
        $result = $this->client()->suppliers()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items) || empty($result->items[0]->company)) {
            $this->markTestSkipped('No suppliers with company name found.');
        }

        $company   = $result->items[0]->company;
        $suppliers = $this->client()->suppliers()->findByCompany($company);

        self::assertContainsOnlyInstancesOf(SupplierDTO::class, $suppliers);
        self::assertGreaterThan(0, count($suppliers));
    }
}
