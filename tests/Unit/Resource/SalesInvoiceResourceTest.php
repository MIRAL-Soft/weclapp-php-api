<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\SalesInvoiceDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SalesInvoiceResource.
 */
class SalesInvoiceResourceTest extends TestCase
{
    private function makeClient(array $responses): WeclappClient
    {
        $mock   = new MockHandler($responses);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);

        return new WeclappClient(
            new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0),
            guzzle: $guzzle,
        );
    }

    private function invoicePayload(string $id = 'inv-1', float $openAmount = 0.0): array
    {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'invoiceNumber'    => 'RE-10042',
            'status'           => 'INVOICE_SENT',
            'customerId'       => 'cust-1',
            'invoiceDate'      => 1711400000000,
            'dueDate'          => 1713992000000,
            'netAmount'        => 100.00,
            'grossAmount'      => 119.00,
            'openAmount'       => $openAmount,
            'invoiceItems'     => [],
            'tags'             => [],
            'customAttributes' => [],
        ];
    }

    public function test_find_returns_sales_invoice_dto(): void
    {
        $client  = $this->makeClient([new Response(200, [], json_encode($this->invoicePayload()))]);
        $invoice = $client->salesInvoices()->find('inv-1');

        self::assertInstanceOf(SalesInvoiceDTO::class, $invoice);
        self::assertSame('RE-10042', $invoice->invoiceNumber);
        self::assertSame('cust-1', $invoice->customerId);
    }

    public function test_get_pdf_returns_binary_data(): void
    {
        $pdfContent = '%PDF-1.4 invoice binary';
        $client     = $this->makeClient([new Response(200, [], $pdfContent)]);

        $result = $client->salesInvoices()->getPdf('inv-1');

        self::assertSame($pdfContent, $result);
    }

    public function test_find_open_returns_invoices_with_open_amount(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [
                $this->invoicePayload('inv-1', 119.00),
                $this->invoicePayload('inv-2', 59.50),
            ]])),
        ]);

        $open = $client->salesInvoices()->findOpen();

        self::assertCount(2, $open);
        self::assertContainsOnlyInstancesOf(SalesInvoiceDTO::class, $open);
    }

    public function test_find_by_customer_returns_list(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->invoicePayload()]])),
        ]);

        $invoices = $client->salesInvoices()->findByCustomer('cust-1');

        self::assertCount(1, $invoices);
        self::assertSame('cust-1', $invoices[0]->customerId);
    }

    public function test_is_open_returns_true_when_open_amount_positive(): void
    {
        $invoice = SalesInvoiceDTO::fromArray($this->invoicePayload('inv-1', 50.0));

        self::assertTrue($invoice->isOpen());
    }

    public function test_is_open_returns_false_when_fully_paid(): void
    {
        $invoice = SalesInvoiceDTO::fromArray($this->invoicePayload('inv-1', 0.0));

        self::assertFalse($invoice->isOpen());
    }

    public function test_due_date_returns_datetime(): void
    {
        $invoice = SalesInvoiceDTO::fromArray($this->invoicePayload());
        $due     = $invoice->getDueDate();

        self::assertNotNull($due);
        self::assertSame(1713992000, $due->getTimestamp());
    }

    public function test_invoice_date_returns_datetime(): void
    {
        $invoice = SalesInvoiceDTO::fromArray($this->invoicePayload());

        self::assertNotNull($invoice->getInvoiceDate());
        self::assertSame(1711400000, $invoice->getInvoiceDate()->getTimestamp());
    }

    public function test_delete_invoice(): void
    {
        $client = $this->makeClient([new Response(204)]);

        $client->salesInvoices()->delete('inv-1');
        $this->addToAssertionCount(1);
    }
}
