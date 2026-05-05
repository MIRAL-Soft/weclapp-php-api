<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use miralsoft\weclapp\api\Enum\SalesOrderStatus;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for SalesOrderResource (/api/v2/salesOrder).
 * All tests are read-only.
 */
class SalesOrderResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_sales_order_dtos(): void
    {
        $result = $this->client()->salesOrders()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertContainsOnlyInstancesOf(SalesOrderDTO::class, $result->items);
    }

    public function test_first_order_has_required_fields(): void
    {
        $result = $this->client()->salesOrders()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales orders found in this tenant.');
        }

        $order = $result->items[0];

        self::assertNotEmpty($order->id);
        self::assertNotEmpty($order->orderNumber);
        self::assertNotEmpty($order->status);
        self::assertIsInt($order->createdDate);
    }

    public function test_order_status_maps_to_known_enum_value(): void
    {
        $result = $this->client()->salesOrders()->list(
            QueryBuilder::new()->pageSize(10),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales orders found in this tenant.');
        }

        foreach ($result->items as $order) {
            $status = SalesOrderStatus::tryFrom($order->status);
            self::assertNotNull(
                $status,
                "Unknown status '{$order->status}' on order {$order->orderNumber} — " .
                'update SalesOrderStatus enum if weclapp added a new value.',
            );
        }
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->salesOrders()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales orders found in this tenant.');
        }

        $id    = $result->items[0]->id;
        $order = $this->client()->salesOrders()->find($id);

        self::assertInstanceOf(SalesOrderDTO::class, $order);
        self::assertSame($id, $order->id);
    }

    public function test_count_returns_positive_integer(): void
    {
        $count = $this->client()->salesOrders()->count();

        self::assertIsInt($count);
        self::assertGreaterThan(0, $count, 'Expected at least one sales order in this tenant.');
    }

    public function test_modified_since_returns_valid_dtos(): void
    {
        $result = $this->client()->salesOrders()->list(
            QueryBuilder::new()
                ->modifiedSince(new \DateTime('-1 year'))
                ->pageSize(5),
        );

        self::assertContainsOnlyInstancesOf(SalesOrderDTO::class, $result->items);
    }

    public function test_find_by_order_number_returns_same_record(): void
    {
        $result = $this->client()->salesOrders()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales orders found in this tenant.');
        }

        $orderNumber = $result->items[0]->orderNumber;
        $order       = $this->client()->salesOrders()->findByOrderNumber($orderNumber);

        self::assertInstanceOf(SalesOrderDTO::class, $order);
        self::assertSame($orderNumber, $order->orderNumber);
    }

    public function test_find_by_customer_returns_sales_order_dtos(): void
    {
        // Use the test-order fixture to get a known-good customerId.
        $anchor     = $this->testSalesOrder();
        $customerId = $anchor?->customerId;

        if (empty($customerId)) {
            // Fallback: take the customerId from the first list entry.
            $result     = $this->client()->salesOrders()->list(QueryBuilder::new()->pageSize(1));
            $customerId = $result->items[0]->customerId ?? null;
        }

        if (empty($customerId)) {
            $this->markTestSkipped('Could not determine a customerId to filter by.');
        }

        $orders = $this->client()->salesOrders()->findByCustomer($customerId);

        self::assertIsArray($orders);
        self::assertContainsOnlyInstancesOf(SalesOrderDTO::class, $orders);
        // The order we used to obtain the customerId must appear in the result.
        if ($anchor !== null) {
            $ids = array_map(static fn (SalesOrderDTO $o): string => $o->id, $orders);
            self::assertContains($anchor->id, $ids, 'The test order must be in the result for its own customerId.');
        }
    }

    public function test_find_by_status_returns_sales_order_dtos(): void
    {
        // Use the test-order fixture so we know at least one record with this status exists.
        $anchor = $this->testSalesOrder();
        $status = $anchor?->status;

        if (empty($status)) {
            // Fallback: use the status of the first list entry.
            $result = $this->client()->salesOrders()->list(QueryBuilder::new()->pageSize(1));
            $status = $result->items[0]->status ?? null;
        }

        if (empty($status)) {
            $this->markTestSkipped('Could not determine a status value to filter by.');
        }

        $orders = $this->client()->salesOrders()->findByStatus($status);

        self::assertIsArray($orders);
        self::assertContainsOnlyInstancesOf(SalesOrderDTO::class, $orders);
        foreach ($orders as $order) {
            self::assertSame($status, $order->status, 'findByStatus() must only return orders with the requested status.');
        }
    }
}
