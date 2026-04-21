<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\PurchaseOrderDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for PurchaseOrderResource (/api/v2/purchaseOrder).
 * All tests are read-only.
 *
 * All tests are automatically skipped if the API token lacks permission
 * to access the purchaseOrder endpoint (HTTP 403).
 */
class PurchaseOrderResourceIntegrationTest extends IntegrationTestCase
{
    /**
     * Skip all tests in this class if the API token has no access to the
     * purchaseOrder endpoint (HTTP 403). This avoids confusing errors when
     * the token is scoped to a subset of weclapp resources.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->client()->purchaseOrders()->list(QueryBuilder::new()->pageSize(1));
        } catch (WeclappApiException $e) {
            if (str_contains($e->getMessage(), 'HTTP 403')) {
                $this->markTestSkipped(
                    'purchaseOrder endpoint returned HTTP 403 — API token lacks permission. ' .
                    'Grant the "Purchase orders" read right in weclapp → Settings → Users.',
                );
            }
            throw $e;
        }
    }

    public function test_list_returns_purchase_order_dtos(): void
    {
        $result = $this->client()->purchaseOrders()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertContainsOnlyInstancesOf(PurchaseOrderDTO::class, $result->items);
    }

    public function test_first_order_has_required_fields(): void
    {
        $result = $this->client()->purchaseOrders()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No purchase orders found in this tenant.');
        }

        $order = $result->items[0];

        self::assertNotEmpty($order->id);
        self::assertNotEmpty($order->purchaseOrderNumber);
        self::assertIsInt($order->createdDate);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->purchaseOrders()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No purchase orders found in this tenant.');
        }

        $id    = $result->items[0]->id;
        $order = $this->client()->purchaseOrders()->find($id);

        self::assertInstanceOf(PurchaseOrderDTO::class, $order);
        self::assertSame($id, $order->id);
    }

    public function test_count_returns_non_negative_integer(): void
    {
        $count = $this->client()->purchaseOrders()->count();

        self::assertIsInt($count);
        self::assertGreaterThanOrEqual(0, $count);
    }

    public function test_find_by_supplier_returns_purchase_order_dtos(): void
    {
        $result = $this->client()->purchaseOrders()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No purchase orders found in this tenant.');
        }

        $supplierId = $result->items[0]->supplierId;

        if (empty($supplierId)) {
            $this->markTestSkipped('First purchase order has no supplierId.');
        }

        $orders = $this->client()->purchaseOrders()->findBySupplier($supplierId);

        self::assertContainsOnlyInstancesOf(PurchaseOrderDTO::class, $orders);
        self::assertGreaterThan(0, count($orders));
    }

    public function test_modified_since_returns_valid_dtos(): void
    {
        $result = $this->client()->purchaseOrders()->list(
            QueryBuilder::new()
                ->modifiedSince(new \DateTime('-1 year'))
                ->pageSize(5),
        );

        self::assertContainsOnlyInstancesOf(PurchaseOrderDTO::class, $result->items);
    }
}
