<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\DocumentDTO;
use miralsoft\weclapp\api\Enum\DocumentType;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for DocumentResource (/api/v2/document).
 *
 * Documents are always queried by entityId + entityName — there is no
 * unrestricted list endpoint. These tests piggyback on an existing sales
 * invoice to verify the document API round-trips correctly.
 *
 * All tests are read-only.
 */
class DocumentResourceIntegrationTest extends IntegrationTestCase
{
    /**
     * Return the ID of the first available sales invoice, or skip.
     */
    private function firstSalesInvoiceId(): string
    {
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales invoices found in this tenant — cannot test document queries.');
        }

        return $result->items[0]->id;
    }

    public function test_find_by_entity_returns_document_dtos(): void
    {
        $invoiceId = $this->firstSalesInvoiceId();

        $docs = $this->client()->documents()->findByEntity($invoiceId, 'salesInvoice');

        self::assertIsArray($docs);
        // A sales invoice may have zero documents attached — that is valid
        self::assertContainsOnlyInstancesOf(DocumentDTO::class, $docs);
    }

    public function test_document_has_required_fields(): void
    {
        // Scan a few invoices to find one that actually has a document attached
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()->pageSize(10),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales invoices found in this tenant.');
        }

        $doc = null;
        foreach ($result->items as $invoice) {
            $docs = $this->client()->documents()->findByEntity($invoice->id, 'salesInvoice');
            if (!empty($docs)) {
                $doc = $docs[0];
                break;
            }
        }

        if ($doc === null) {
            $this->markTestSkipped('None of the first 10 invoices have documents attached.');
        }

        self::assertNotEmpty($doc->id);
        self::assertNotEmpty($doc->documentType);
        self::assertIsInt($doc->createdDate);
    }

    public function test_find_by_entity_and_type_filters_correctly(): void
    {
        // Scan a few invoices to find one with a SALES_INVOICE type document
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()->pageSize(10),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales invoices found in this tenant.');
        }

        $foundInvoiceId = null;
        foreach ($result->items as $invoice) {
            $docs = $this->client()->documents()->findByEntityAndType(
                $invoice->id,
                'salesInvoice',
                DocumentType::SalesInvoice,
            );
            if (!empty($docs)) {
                $foundInvoiceId = $invoice->id;
                // Every returned document must match the requested type
                foreach ($docs as $doc) {
                    self::assertSame(DocumentType::SalesInvoice->value, $doc->documentType);
                }
                break;
            }
        }

        if ($foundInvoiceId === null) {
            $this->markTestSkipped('No SALES_INVOICE documents found on first 10 invoices.');
        }
    }

    public function test_find_by_id_returns_same_record(): void
    {
        // Find an invoice with at least one document
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()->pageSize(10),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No sales invoices found in this tenant.');
        }

        $doc = null;
        foreach ($result->items as $invoice) {
            $docs = $this->client()->documents()->findByEntity($invoice->id, 'salesInvoice');
            if (!empty($docs)) {
                $doc = $docs[0];
                break;
            }
        }

        if ($doc === null) {
            $this->markTestSkipped('None of the first 10 invoices have documents attached.');
        }

        $fetched = $this->client()->documents()->find($doc->id);

        self::assertInstanceOf(DocumentDTO::class, $fetched);
        self::assertSame($doc->id, $fetched->id);
    }

    public function test_find_cancellation_document_returns_dto_or_null(): void
    {
        // Scan a few invoices to find a cancelled one
        $result = $this->client()->salesInvoices()->list(
            QueryBuilder::new()
                ->filterEq('status', 'CANCELLED')
                ->pageSize(3),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No cancelled sales invoices found in this tenant.');
        }

        $cancelledInvoice = $result->items[0];

        $doc = $this->client()->documents()->findCancellationDocument($cancelledInvoice->id);

        // Either null (cancellation processed externally) or a DocumentDTO
        if ($doc !== null) {
            self::assertInstanceOf(DocumentDTO::class, $doc);
            self::assertSame(DocumentType::SalesInvoiceCancellation->value, $doc->documentType);
        } else {
            // Cancellation document not attached — this is valid, just mark as incomplete
            $this->addWarning(
                "Cancelled invoice {$cancelledInvoice->id} has no SALES_INVOICE_CANCELLATION document attached.",
            );
        }
    }
}
