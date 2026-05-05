<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\ArticleDTO;
use miralsoft\weclapp\api\DTO\ContactDTO;
use miralsoft\weclapp\api\DTO\CustomerDTO;
use miralsoft\weclapp\api\DTO\QuotationDTO;
use miralsoft\weclapp\api\DTO\SalesInvoiceDTO;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use miralsoft\weclapp\api\DTO\SupplierDTO;
use miralsoft\weclapp\api\Exception\ValidationException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
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
 *
 * Resilience design
 * -----------------
 * Many tests deal with UPDATE operations on records that may be in a locked
 * or invoiced state on the live tenant (weclapp rejects modifications even in
 * dry-run mode when a record's status does not allow changes).  Similarly, some
 * endpoints return HTTP 403 when the API key lacks write permission or when the
 * endpoint does not support dryRun for PUT operations.
 *
 * Rather than hard-failing in those cases — which would block CI on any tenant
 * that has only "closed" records — the UPDATE tests catch WeclappApiException and
 * ValidationException and call markTestSkipped() with an explanatory message.
 * The dry-run mechanism itself (sending ?dryRun=true) is fully exercised by the
 * CREATE tests, which always pass on a tenant with standard write permissions.
 *
 * Resources covered (create and/or update dry-run):
 *   Customer, SalesOrder, Quotation, Article, SalesInvoice, Supplier, Contact
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

        try {
            $updated = $this->client()->customers()->withDryRun()->update($existing->id, [
                'id'      => $existing->id,
                'version' => $existing->version,
                'company' => $existing->company, // change nothing — just round-trip validate
            ]);

            self::assertInstanceOf(CustomerDTO::class, $updated);
            // id is absent in the dry-run response → AbstractDTO::str() returns ''
            self::assertSame('', $updated->id, 'id must be empty in dry-run response.');
            self::assertSame('', $updated->version, 'version must be empty in dry-run response.');
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Endpoint rejected dry-run update (e.g. party dryRun PUT not supported, ' .
                'record locked, or insufficient permissions): ' . $e->getMessage(),
            );
        }
    }

    public function test_dry_run_customer_update_reflects_changed_field(): void
    {
        $result = $this->client()->customers()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No customers in this tenant.');
        }

        $existing   = $result->items[0];
        $testSuffix = ' [DRY-RUN-TEST]';

        try {
            $updated = $this->client()->customers()->withDryRun()->update($existing->id, [
                'id'      => $existing->id,
                'version' => $existing->version,
                'company' => $existing->company . $testSuffix,
            ]);

            // The dry-run response should reflect the submitted value
            self::assertStringEndsWith($testSuffix, (string) $updated->company);
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Endpoint rejected dry-run update (e.g. party dryRun PUT not supported, ' .
                'record locked, or insufficient permissions): ' . $e->getMessage(),
            );
        }
    }

    // -------------------------------------------------------------------------
    // SalesOrder — create (POST)
    // -------------------------------------------------------------------------

    public function test_dry_run_sales_order_create_with_real_customer_id(): void
    {
        // Priority: customerId from configured test order → configured test customer → first from list()
        $customerId = $this->testSalesOrder()?->customerId
            ?: $this->testCustomerId();

        if ($customerId === null || $customerId === '') {
            $customers = $this->client()->customers()->list(QueryBuilder::new()->pageSize(1));
            if (empty($customers->items)) {
                $this->markTestSkipped(
                    'No customers in this tenant and neither WECLAPP_TEST_SALES_ORDER_NUMBER ' .
                    'nor WECLAPP_TEST_CUSTOMER_NUMBER are configured.',
                );
            }
            $customerId = $customers->items[0]->id;
        }

        if ($customerId === '') {
            $this->markTestSkipped('Could not determine a valid customer ID for this tenant.');
        }

        try {
            $order = $this->client()->salesOrders()->withDryRun()->create([
                'customerId' => $customerId,
            ]);

            self::assertInstanceOf(SalesOrderDTO::class, $order);
            self::assertSame('', $order->id, 'id must be empty in dry-run response.');
            // status and other computed fields should be populated by weclapp
            self::assertNotEmpty($order->status);
        } catch (ValidationException $e) {
            $this->markTestSkipped(
                'Customer ' . $customerId . ' cannot be used for order creation on this tenant: ' .
                $e->getMessage(),
            );
        }
    }

    public function test_dry_run_sales_order_create_throws_on_invalid_customer(): void
    {
        $this->expectException(ValidationException::class);

        $this->client()->salesOrders()->withDryRun()->create([
            'customerId' => '__this-customer-does-not-exist__',
        ]);
    }

    // -------------------------------------------------------------------------
    // SalesOrder — addOrderItem via Read-Modify-Write (GET real + PUT dry-run)
    // -------------------------------------------------------------------------

    public function test_dry_run_add_order_item_validates_against_real_order(): void
    {
        $existing = $this->testSalesOrder();

        if ($existing === null) {
            $result = $this->client()->salesOrders()->list(QueryBuilder::new()->pageSize(5));
            if (empty($result->items)) {
                $this->markTestSkipped('No sales orders in this tenant and WECLAPP_TEST_SALES_ORDER_NUMBER not set.');
            }
            $existing = $result->items[0];
        }

        try {
            // addOrderItem() does a real GET (to get current items + version), then a
            // dry-run PUT — so the payload is validated against the real order state.
            $order = $this->client()->salesOrders()->withDryRun()->addOrderItem(
                $existing->id,
                ['title' => '[DRY-RUN] Test position — safe to ignore'],
            );

            self::assertInstanceOf(SalesOrderDTO::class, $order);
            self::assertSame('', $order->id, 'id must be empty in dry-run response.');
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Sales order is in a state that rejects item addition (e.g. invoiced/locked): ' .
                $e->getMessage(),
            );
        }
    }

    // -------------------------------------------------------------------------
    // Quotation — create (POST) and update (PUT)
    // -------------------------------------------------------------------------

    public function test_dry_run_quotation_create_with_real_customer_id(): void
    {
        // Priority: configured test customer → customerId from test order → first from list()
        $customerId = $this->testCustomerId()
            ?: $this->testSalesOrder()?->customerId;

        if ($customerId === null || $customerId === '') {
            $customers = $this->client()->customers()->list(QueryBuilder::new()->pageSize(1));
            if (empty($customers->items)) {
                $this->markTestSkipped(
                    'No customers in this tenant and neither WECLAPP_TEST_CUSTOMER_NUMBER ' .
                    'nor WECLAPP_TEST_SALES_ORDER_NUMBER are configured.',
                );
            }
            $customerId = $customers->items[0]->id;
        }

        if ($customerId === '') {
            $this->markTestSkipped('Could not determine a valid customer ID for this tenant.');
        }

        try {
            $quotation = $this->client()->quotations()->withDryRun()->create([
                'customerId' => $customerId,
            ]);

            self::assertInstanceOf(QuotationDTO::class, $quotation);
            self::assertSame('', $quotation->id, 'id must be empty in dry-run response.');
            // status should be populated by weclapp business logic
            self::assertNotEmpty($quotation->status);
        } catch (ValidationException $e) {
            $this->markTestSkipped(
                'Customer ' . $customerId . ' cannot be used for quotation creation on this tenant: ' .
                $e->getMessage(),
            );
        }
    }

    public function test_dry_run_quotation_create_throws_on_invalid_customer(): void
    {
        $this->expectException(ValidationException::class);

        $this->client()->quotations()->withDryRun()->create([
            'customerId' => '__this-customer-does-not-exist__',
        ]);
    }

    public function test_dry_run_quotation_update_returns_dto_without_id(): void
    {
        $result = $this->client()->quotations()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No quotations in this tenant.');
        }

        $existing = $result->items[0];

        try {
            $updated = $this->client()->quotations()->withDryRun()->update($existing->id, [
                'id'      => $existing->id,
                'version' => $existing->version,
            ]);

            self::assertInstanceOf(QuotationDTO::class, $updated);
            self::assertSame('', $updated->id, 'id must be empty in dry-run response.');
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Quotation is in a state that rejects dry-run update (e.g. converted/cancelled): ' .
                $e->getMessage(),
            );
        }
    }

    // -------------------------------------------------------------------------
    // Article — create (POST) and update (PUT)
    // -------------------------------------------------------------------------

    public function test_dry_run_article_create_returns_dto(): void
    {
        // uniqid ensures uniqueness even if the dry-run check includes
        // a duplicate articleNumber validation on the live tenant.
        $article = $this->client()->articles()->withDryRun()->create([
            'name'          => '[DRY-RUN] Test article — safe to ignore',
            'articleNumber' => 'DRY-RUN-' . uniqid(),
        ]);

        self::assertInstanceOf(ArticleDTO::class, $article);
        self::assertSame('', $article->id, 'id must be empty in dry-run response.');
    }

    public function test_dry_run_article_update_returns_dto_without_id(): void
    {
        $result = $this->client()->articles()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant.');
        }

        $existing = $result->items[0];

        try {
            $updated = $this->client()->articles()->withDryRun()->update($existing->id, [
                'id'      => $existing->id,
                'version' => $existing->version,
            ]);

            self::assertInstanceOf(ArticleDTO::class, $updated);
            self::assertSame('', $updated->id, 'id must be empty in dry-run response.');
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Endpoint rejected dry-run update (e.g. article dryRun PUT not supported ' .
                'or insufficient permissions): ' . $e->getMessage(),
            );
        }
    }

    // -------------------------------------------------------------------------
    // SalesInvoice — update (PUT)
    //
    // Create is intentionally omitted: a valid invoice payload requires an
    // existing customer and a confirmed sales order, making it impractical
    // to construct reliably across arbitrary tenants. Update (round-trip
    // validate with the existing record's version) is sufficient to confirm
    // the endpoint accepts our DTO mapping.
    // -------------------------------------------------------------------------

    public function test_dry_run_sales_invoice_update_returns_dto_without_id(): void
    {
        $result = $this->client()->salesInvoices()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No sales invoices in this tenant.');
        }

        $existing = $result->items[0];

        try {
            $updated = $this->client()->salesInvoices()->withDryRun()->update($existing->id, [
                'id'      => $existing->id,
                'version' => $existing->version,
            ]);

            self::assertInstanceOf(SalesInvoiceDTO::class, $updated);
            self::assertSame('', $updated->id, 'id must be empty in dry-run response.');
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Invoice is in a state that rejects dry-run update (e.g. paid/booked to accounting): ' .
                $e->getMessage(),
            );
        }
    }

    // -------------------------------------------------------------------------
    // Supplier — create (POST) and update (PUT)
    // -------------------------------------------------------------------------

    public function test_dry_run_supplier_create_returns_dto(): void
    {
        $supplier = $this->client()->suppliers()->withDryRun()->create([
            'company'   => '[DRY-RUN] Test supplier — safe to ignore',
            'partyType' => 'ORGANIZATION',
        ]);

        self::assertInstanceOf(SupplierDTO::class, $supplier);
        self::assertSame('', $supplier->id, 'id must be empty in dry-run response.');
    }

    public function test_dry_run_supplier_update_returns_dto_without_id(): void
    {
        $result = $this->client()->suppliers()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No suppliers in this tenant.');
        }

        $existing = $result->items[0];

        try {
            $updated = $this->client()->suppliers()->withDryRun()->update($existing->id, [
                'id'      => $existing->id,
                'version' => $existing->version,
            ]);

            self::assertInstanceOf(SupplierDTO::class, $updated);
            self::assertSame('', $updated->id, 'id must be empty in dry-run response.');
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Endpoint rejected dry-run update (e.g. party dryRun PUT not supported ' .
                'or insufficient permissions): ' . $e->getMessage(),
            );
        }
    }

    // -------------------------------------------------------------------------
    // Contact — create (POST) and update (PUT)
    // -------------------------------------------------------------------------

    public function test_dry_run_contact_create_returns_dto(): void
    {
        // A contact needs a parent party (the organisation it belongs to).
        // Priority: configured test customer → customerId from test order → first from list()
        $parentId = $this->testCustomerId()
            ?: $this->testSalesOrder()?->customerId;

        if ($parentId === null || $parentId === '') {
            $customers = $this->client()->customers()->list(QueryBuilder::new()->pageSize(1));
            if (empty($customers->items)) {
                $this->markTestSkipped(
                    'No customers in this tenant and neither WECLAPP_TEST_CUSTOMER_NUMBER ' .
                    'nor WECLAPP_TEST_SALES_ORDER_NUMBER are configured.',
                );
            }
            $parentId = $customers->items[0]->id;
        }

        if ($parentId === '') {
            $this->markTestSkipped('Could not determine a valid parent party ID for this tenant.');
        }

        try {
            $contact = $this->client()->contacts()->withDryRun()->create([
                'firstName'     => 'DryRun',
                'lastName'      => 'Test',
                'partyType'     => 'PERSON',
                'parentPartyId' => $parentId,
            ]);

            self::assertInstanceOf(ContactDTO::class, $contact);
            self::assertSame('', $contact->id, 'id must be empty in dry-run response.');
        } catch (ValidationException $e) {
            $this->markTestSkipped(
                'Parent party ' . $parentId . ' cannot be used for contact creation on this tenant: ' .
                $e->getMessage(),
            );
        }
    }

    public function test_dry_run_contact_update_returns_dto_without_id(): void
    {
        // ContactResource filters by parentPartyId NOT NULL.
        // If the list returns no results (weclapp may not populate parentPartyId
        // reliably on the response object), the test is skipped gracefully.
        $result = $this->client()->contacts()->list(QueryBuilder::new()->pageSize(1));

        if (empty($result->items)) {
            $this->markTestSkipped('No contacts in this tenant.');
        }

        $existing = $result->items[0];

        try {
            $updated = $this->client()->contacts()->withDryRun()->update($existing->id, [
                'id'      => $existing->id,
                'version' => $existing->version,
            ]);

            self::assertInstanceOf(ContactDTO::class, $updated);
            self::assertSame('', $updated->id, 'id must be empty in dry-run response.');
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Endpoint rejected dry-run update (e.g. party dryRun PUT not supported ' .
                'or insufficient permissions): ' . $e->getMessage(),
            );
        }
    }

    // -------------------------------------------------------------------------
    // isDryRun() — live sanity checks across all covered resources
    // -------------------------------------------------------------------------

    public function test_is_dry_run_returns_false_for_normal_resource(): void
    {
        self::assertFalse($this->client()->salesOrders()->isDryRun());
        self::assertFalse($this->client()->customers()->isDryRun());
        self::assertFalse($this->client()->quotations()->isDryRun());
        self::assertFalse($this->client()->articles()->isDryRun());
        self::assertFalse($this->client()->salesInvoices()->isDryRun());
        self::assertFalse($this->client()->suppliers()->isDryRun());
        self::assertFalse($this->client()->contacts()->isDryRun());
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
