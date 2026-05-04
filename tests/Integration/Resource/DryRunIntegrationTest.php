<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\CustomerDTO;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use miralsoft\weclapp\api\Exception\ValidationException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for dry-run mode against the live weclapp API.
 *
 * These tests are SAFE to run against any tenant, including production:
 * every write call appends ?dryRun=true — weclapp validates the payload
 * and runs business logic but never persists any data.
 *
 * What is verified here that unit tests cannot:
 *   - Real weclapp endpoints actually accept our payloads (no 400 / 500 surprises).
 *   - Business-logic validation runs on the live tenant (e.g. duplicate checks).
 *   - The 200 response body parses cleanly into our DTO classes.
 *   - Invalid payloads produce the expected ValidationException.
 */
class DryRunIntegrationTest extends IntegrationTestCase
{
    // -------------------------------------------------------------------------
    // Customer — update (PUT)
    // -------------------------------------------------------------------------

    public function test_dry_run_customer_update_returns_dto_without_id(): void
    {
        $result = $this->client()->customers()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No customers in this tenant.');
        }

        $existing = $result->items[0];

        $updated = $this->client()->customers()->withDryRun()->update($existing->id, [
            'version' => $existing->version,
            'company' => $existing->company, // change nothing — just round-trip validate
        ]);

        self::assertInstanceOf(CustomerDTO::class, $updated);
        // id is absent in the dry-run response → AbstractDTO::str() returns ''
        self::assertSame('', $updated->id, 'id must be empty in dry-run response.');
        self::assertSame('', $updated->version, 'version must be empty in dry-run response.');
    }

    public function test_dry_run_customer_update_reflects_changed_field(): void
    {
        $result = $this->client()->customers()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No customers in this tenant.');
        }

        $existing   = $result->items[0];
        $testSuffix = ' [DRY-RUN-TEST]';

        $updated = $this->client()->customers()->withDryRun()->update($existing->id, [
            'version' => $existing->version,
            'company' => $existing->company . $testSuffix,
        ]);

        // The dry-run response should reflect the submitted value
        self::assertStringEndsWith($testSuffix, (string) $updated->company);
    }

    // -------------------------------------------------------------------------
    // SalesOrder — create (POST)
    // -------------------------------------------------------------------------

    public function test_dry_run_sales_order_create_with_real_customer_id(): void
    {
        // Prefer WECLAPP_TEST_CUSTOMER_ID for deterministic results; fall back to list()
        $customerId = $this->testCustomerId();

        if ($customerId === null) {
            $customers = $this->client()->customers()->list(QueryBuilder::new()->pageSize(1));
            if (empty($customers->items)) {
                $this->markTestSkipped('No customers in this tenant and WECLAPP_TEST_CUSTOMER_ID not set.');
            }
            $customerId = $customers->items[0]->id;
        }

        $order = $this->client()->salesOrders()->withDryRun()->create([
            'customerId' => $customerId,
        ]);

        self::assertInstanceOf(SalesOrderDTO::class, $order);
        self::assertSame('', $order->id, 'id must be empty in dry-run response.');
        // status and other computed fields should be populated by weclapp
        self::assertNotEmpty($order->status);
    }

    public function test_dry_run_sales_order_create_throws_on_invalid_customer(): void
    {
        $this->expectException(ValidationException::class);

        $this->client()->salesOrders()->withDryRun()->create([
            'customerId' => '__this-customer-does-not-exist__',
        ]);
    }

    // -------------------------------------------------------------------------
    // SalesOrder — update (PUT)
    // -------------------------------------------------------------------------

    public function test_dry_run_sales_order_update_returns_dto_without_id(): void
    {
        $result = $this->client()->salesOrders()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No sales orders in this tenant.');
        }

        $existing = $result->items[0];

        $updated = $this->client()->salesOrders()->withDryRun()->update($existing->id, [
            'version' => $existing->version,
        ]);

        self::assertInstanceOf(SalesOrderDTO::class, $updated);
        self::assertSame('', $updated->id, 'id must be empty in dry-run response.');
    }

    // -------------------------------------------------------------------------
    // SalesOrder — addOrderItem via Read-Modify-Write (GET real + PUT dry-run)
    // -------------------------------------------------------------------------

    public function test_dry_run_add_order_item_validates_against_real_order(): void
    {
        $result = $this->client()->salesOrders()->list(QueryBuilder::new()->pageSize(5));

        if (empty($result->items)) {
            $this->markTestSkipped('No sales orders in this tenant.');
        }

        // addOrderItem() does a real GET (to get current items + version), then a
        // dry-run PUT — so the payload is validated against the real order state.
        $order = $this->client()->salesOrders()->withDryRun()->addOrderItem(
            $result->items[0]->id,
            ['title' => '[DRY-RUN] Test position — safe to ignore'],
        );

        self::assertInstanceOf(SalesOrderDTO::class, $order);
        self::assertSame('', $order->id, 'id must be empty in dry-run response.');
    }

    // -------------------------------------------------------------------------
    // isDryRun() — live sanity check
    // -------------------------------------------------------------------------

    public function test_is_dry_run_returns_false_for_normal_resource(): void
    {
        self::assertFalse($this->client()->salesOrders()->isDryRun());
        self::assertFalse($this->client()->customers()->isDryRun());
    }

    public function test_is_dry_run_returns_true_for_cloned_resource(): void
    {
        self::assertTrue($this->client()->salesOrders()->withDryRun()->isDryRun());
    }

    public function test_dry_run_clone_does_not_affect_original(): void
    {
        $resource = $this->client()->salesOrders();
        $resource->withDryRun(); // create and discard clone

        self::assertFalse($resource->isDryRun(), 'Original must be unaffected by withDryRun().');
    }
}
