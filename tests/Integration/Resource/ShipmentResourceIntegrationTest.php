<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\ShipmentDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for ShipmentResource (/api/v2/shipment).
 * All tests are read-only.
 *
 * All tests are automatically skipped if the API token lacks permission
 * to access the shipment endpoint (HTTP 403).
 */
class ShipmentResourceIntegrationTest extends IntegrationTestCase
{
    /**
     * Skip all tests in this class if the API token has no access to the
     * shipment endpoint (HTTP 403). This avoids confusing errors when the
     * token is scoped to a subset of weclapp resources.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->client()->shipments()->list(QueryBuilder::new()->pageSize(1));
        } catch (WeclappApiException $e) {
            if (str_contains($e->getMessage(), 'HTTP 403')) {
                $this->markTestSkipped(
                    'shipment endpoint returned HTTP 403 — API token lacks permission. ' .
                    'Grant the "Shipments" read right in weclapp → Settings → Users.',
                );
            }
            throw $e;
        }
    }

    public function test_list_returns_shipment_dtos(): void
    {
        $result = $this->client()->shipments()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertContainsOnlyInstancesOf(ShipmentDTO::class, $result->items);
    }

    public function test_first_shipment_has_required_fields(): void
    {
        $result = $this->client()->shipments()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No shipments found in this tenant.');
        }

        $shipment = $result->items[0];

        self::assertNotEmpty($shipment->id);
        self::assertNotEmpty($shipment->shipmentNumber);
        self::assertIsInt($shipment->createdDate);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->shipments()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No shipments found in this tenant.');
        }

        $id       = $result->items[0]->id;
        $shipment = $this->client()->shipments()->find($id);

        self::assertInstanceOf(ShipmentDTO::class, $shipment);
        self::assertSame($id, $shipment->id);
    }

    public function test_count_returns_non_negative_integer(): void
    {
        $count = $this->client()->shipments()->count();

        self::assertIsInt($count);
        self::assertGreaterThanOrEqual(0, $count);
    }

    public function test_find_by_sales_order_returns_shipment_dtos(): void
    {
        $result = $this->client()->shipments()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No shipments found in this tenant.');
        }

        $salesOrderId = $result->items[0]->mainSalesOrderId ?? null;

        if (empty($salesOrderId)) {
            $this->markTestSkipped('First shipment has no mainSalesOrderId.');
        }

        $shipments = $this->client()->shipments()->findBySalesOrder($salesOrderId);

        self::assertContainsOnlyInstancesOf(ShipmentDTO::class, $shipments);
        self::assertGreaterThan(0, count($shipments));
    }

    public function test_modified_since_returns_valid_dtos(): void
    {
        $result = $this->client()->shipments()->list(
            QueryBuilder::new()
                ->modifiedSince(new \DateTime('-1 year'))
                ->pageSize(5),
        );

        self::assertContainsOnlyInstancesOf(ShipmentDTO::class, $result->items);
    }
}
