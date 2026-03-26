<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SalesOrderResource.
 */
class SalesOrderResourceTest extends TestCase
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

    private function orderPayload(string $id = 'ord-1'): array
    {
        return [
            'id'              => $id,
            'version'         => '1',
            'createdDate'     => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'orderNumber'     => 'SO-10042',
            'status'          => 'ORDER_CONFIRMED',
            'customerId'      => 'cust-1',
            'orderDate'       => 1711400000000,
            'orderItems'      => [],
            'tags'            => [],
            'customAttributes' => [],
        ];
    }

    public function test_find_returns_sales_order_dto(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode($this->orderPayload()))]);
        $order  = $client->salesOrders()->find('ord-1');

        self::assertInstanceOf(SalesOrderDTO::class, $order);
        self::assertSame('SO-10042', $order->orderNumber);
        self::assertSame('ORDER_CONFIRMED', $order->status);
    }

    public function test_get_pdf_returns_binary_data(): void
    {
        $pdfContent = '%PDF-1.4 binary content';
        $client     = $this->makeClient([new Response(200, [], $pdfContent)]);

        $result = $client->salesOrders()->getPdf('ord-1');

        self::assertSame($pdfContent, $result);
    }

    public function test_find_by_customer_returns_list(): void
    {
        // First call: list() page 1 (returns 1 item, fewer than pageSize → no more pages)
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->orderPayload()]])),
        ]);

        $orders = $client->salesOrders()->findByCustomer('cust-1');

        self::assertCount(1, $orders);
        self::assertSame('cust-1', $orders[0]->customerId);
    }

    public function test_create_order_returns_dto(): void
    {
        $client = $this->makeClient([
            new Response(201, [], json_encode($this->orderPayload('new-ord'))),
        ]);

        $order = $client->salesOrders()->create(['customerId' => 'cust-1']);

        self::assertSame('new-ord', $order->id);
    }

    public function test_order_date_returns_datetime(): void
    {
        $order = SalesOrderDTO::fromArray($this->orderPayload());
        $dt    = $order->getOrderDate();

        self::assertNotNull($dt);
        self::assertSame(1711400000, $dt->getTimestamp());
    }
}
