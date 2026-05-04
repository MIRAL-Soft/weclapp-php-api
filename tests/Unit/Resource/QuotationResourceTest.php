<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\QuotationDTO;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QuotationResource.
 */
class QuotationResourceTest extends TestCase
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

    private function quotationPayload(string $id = 'q-1'): array
    {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'quotationNumber'  => 'A-1001',
            'status'           => 'QUOTATION_IN_PROCESS',
            'customerId'       => 'cust-1',
            'quotationDate'    => 1711400000000,
            'validUntilDate'   => 1713992000000,
            'quotationItems'   => [],
            'tags'             => [],
            'customAttributes' => [],
        ];
    }

    private function salesOrderPayload(string $id = 'ord-1'): array
    {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'orderNumber'      => 'SO-2001',
            'status'           => 'ORDER_CONFIRMED',
            'customerId'       => 'cust-1',
            'orderDate'        => 1711400000000,
            'orderItems'       => [],
            'tags'             => [],
            'customAttributes' => [],
        ];
    }

    public function test_find_returns_quotation_dto(): void
    {
        $client    = $this->makeClient([new Response(200, [], json_encode($this->quotationPayload()))]);
        $quotation = $client->quotations()->find('q-1');

        self::assertInstanceOf(QuotationDTO::class, $quotation);
        self::assertSame('A-1001', $quotation->quotationNumber);
    }

    public function test_convert_to_sales_order_returns_sales_order_dto(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->salesOrderPayload())),
        ]);

        $order = $client->quotations()->convertToSalesOrder('q-1');

        self::assertInstanceOf(SalesOrderDTO::class, $order);
        self::assertSame('SO-2001', $order->orderNumber);
        self::assertSame('ORDER_CONFIRMED', $order->status);
    }

    public function test_get_pdf_returns_binary(): void
    {
        $pdfContent = '%PDF-1.4 quotation binary';
        $client     = $this->makeClient([new Response(200, [], $pdfContent)]);

        $result = $client->quotations()->getPdf('q-1');

        self::assertSame($pdfContent, $result);
    }

    public function test_find_by_customer_returns_list(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->quotationPayload()]])),
        ]);

        $quotations = $client->quotations()->findByCustomer('cust-1');

        self::assertCount(1, $quotations);
        self::assertSame('cust-1', $quotations[0]->customerId);
    }

    // -------------------------------------------------------------------------
    // findByQuotationNumber
    // -------------------------------------------------------------------------

    public function test_find_by_quotation_number_returns_matching_quotation(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->quotationPayload('q-99')]])),
        ]);

        $quotation = $client->quotations()->findByQuotationNumber('A-1001');

        self::assertInstanceOf(QuotationDTO::class, $quotation);
        self::assertSame('q-99', $quotation->id);
        self::assertSame('A-1001', $quotation->quotationNumber);
    }

    public function test_find_by_quotation_number_throws_not_found_when_missing(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('A-UNKNOWN');

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $client->quotations()->findByQuotationNumber('A-UNKNOWN');
    }
}
