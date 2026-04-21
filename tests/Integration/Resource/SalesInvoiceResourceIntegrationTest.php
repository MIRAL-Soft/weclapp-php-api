<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\SalesInvoiceDTO;
use miralsoft\weclapp\api\Enum\SalesInvoiceStatus;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for SalesInvoiceResource.
 *
 * All tests are read-only — no data is created or modified.
 */
class SalesInvoiceResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_paginated_result(): void
    {
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertGreaterThanOrEqual(0, $result->total);
        self::assertContainsOnlyInstancesOf(SalesInvoiceDTO::class, $result->items);
    }

    public function test_first_invoice_has_required_fields(): void
    {
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales invoices in this tenant.');
        }

        $invoice = $result->items[0];

        self::assertNotEmpty($invoice->id);
        self::assertNotEmpty($invoice->invoiceNumber);
        self::assertNotEmpty($invoice->status);
        self::assertIsInt($invoice->createdDate);
    }

    public function test_invoice_status_maps_to_known_enum_value(): void
    {
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()->pageSize(10),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales invoices in this tenant.');
        }

        foreach ($result->items as $invoice) {
            $status = SalesInvoiceStatus::tryFrom($invoice->status);

            self::assertNotNull(
                $status,
                "Unknown status '{$invoice->status}' on invoice {$invoice->invoiceNumber} — " .
                'update SalesInvoiceStatus enum if weclapp added a new value.',
            );
        }
    }

    public function test_find_by_id_returns_same_invoice(): void
    {
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales invoices in this tenant.');
        }

        $id      = $result->items[0]->id;
        $invoice = $this->client()->salesInvoices()->find($id);

        self::assertInstanceOf(SalesInvoiceDTO::class, $invoice);
        self::assertSame($id, $invoice->id);
    }

    public function test_count_returns_positive_integer(): void
    {
        // count() calls /salesInvoice/count and returns the real total.
        // list().total is NOT the global count — weclapp list responses do not
        // include a recordCount field, so total falls back to items-on-page.
        $count = $this->client()->salesInvoices()->count();

        self::assertIsInt($count);
        self::assertGreaterThan(0, $count, 'Expected at least one sales invoice in this tenant.');
    }
}
