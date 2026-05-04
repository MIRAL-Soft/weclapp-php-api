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
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\OptimisticLockException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
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

    private function orderPayload(string $id = 'ord-1', array $orderItems = []): array
    {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'orderNumber'      => 'SO-10042',
            'status'           => 'ORDER_CONFIRMED',
            'customerId'       => 'cust-1',
            'orderDate'        => 1711400000000,
            'orderItems'       => $orderItems,
            'tags'             => [],
            'customAttributes' => [],
        ];
    }

    /** Minimal valid order-item payload that satisfies SalesOrderItemDTO::fromArray(). */
    private function itemPayload(string $id = 'item-1', string $articleId = 'art-1'): array
    {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'articleId'        => $articleId,
            'title'            => 'Test Item',
            'positionNumber'   => 1,
            'quantity'         => '1.00',
            'unitPrice'        => '10.00',
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

    // -------------------------------------------------------------------------
    // findByOrderNumber
    // -------------------------------------------------------------------------

    public function test_find_by_order_number_returns_matching_order(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->orderPayload('ord-99')]])),
        ]);

        $order = $client->salesOrders()->findByOrderNumber('SO-10042');

        self::assertInstanceOf(SalesOrderDTO::class, $order);
        self::assertSame('ord-99', $order->id);
        self::assertSame('SO-10042', $order->orderNumber);
    }

    public function test_find_by_order_number_throws_not_found_when_missing(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('SO-UNKNOWN');

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $client->salesOrders()->findByOrderNumber('SO-UNKNOWN');
    }

    // -------------------------------------------------------------------------
    // addOrderItem
    // -------------------------------------------------------------------------

    public function test_add_order_item_appends_item_and_returns_updated_order(): void
    {
        // GET returns empty order; PUT returns order with one item
        $returnedItem  = $this->itemPayload('item-new');
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload('ord-1', []))),
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$returnedItem]))),
        ]);

        $updated = $client->salesOrders()->addOrderItem('ord-1', ['articleId' => 'art-1', 'quantity' => '2.00']);

        self::assertInstanceOf(SalesOrderDTO::class, $updated);
        self::assertCount(1, $updated->orderItems);
    }

    public function test_add_order_item_accepts_title_without_article_id(): void
    {
        $returnedItem = $this->itemPayload('item-ft');
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload())),
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$returnedItem]))),
        ]);

        $updated = $client->salesOrders()->addOrderItem('ord-1', ['title' => 'Free-text position']);

        self::assertCount(1, $updated->orderItems);
    }

    public function test_add_order_item_throws_when_article_id_and_title_are_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires either "articleId" or "title"');

        $client = $this->makeClient([]); // no HTTP calls expected
        $client->salesOrders()->addOrderItem('ord-1', ['quantity' => '1.00']);
    }

    public function test_add_order_item_throws_not_found_when_order_missing(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([new Response(404, [], json_encode(['error' => 'not found']))]);
        $client->salesOrders()->addOrderItem('ord-missing', ['articleId' => 'art-1']);
    }

    public function test_add_order_item_propagates_optimistic_lock_exception(): void
    {
        $this->expectException(OptimisticLockException::class);

        // GET succeeds; PUT returns 409 (concurrent modification)
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload())),
            new Response(409, [], json_encode(['error' => 'version conflict'])),
        ]);

        $client->salesOrders()->addOrderItem('ord-1', ['articleId' => 'art-1']);
    }

    // -------------------------------------------------------------------------
    // updateOrderItem
    // -------------------------------------------------------------------------

    public function test_update_order_item_merges_data_and_returns_updated_order(): void
    {
        $existingItem  = $this->itemPayload('item-1');
        $updatedItem   = array_merge($existingItem, ['quantity' => '5.00']);

        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$existingItem]))),
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$updatedItem]))),
        ]);

        $updated = $client->salesOrders()->updateOrderItem('ord-1', 'item-1', ['quantity' => '5.00']);

        self::assertInstanceOf(SalesOrderDTO::class, $updated);
        self::assertSame('5.00', $updated->orderItems[0]->quantity);
    }

    public function test_update_order_item_throws_not_found_when_item_id_missing(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('item-ghost');

        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$this->itemPayload('item-1')]))),
        ]);

        $client->salesOrders()->updateOrderItem('ord-1', 'item-ghost', ['quantity' => '3.00']);
    }

    public function test_update_order_item_propagates_optimistic_lock_exception(): void
    {
        $this->expectException(OptimisticLockException::class);

        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$this->itemPayload('item-1')]))),
            new Response(409, [], json_encode(['error' => 'version conflict'])),
        ]);

        $client->salesOrders()->updateOrderItem('ord-1', 'item-1', ['quantity' => '9.00']);
    }

    // -------------------------------------------------------------------------
    // removeOrderItem
    // -------------------------------------------------------------------------

    public function test_remove_order_item_removes_item_and_returns_updated_order(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$this->itemPayload('item-1')]))),
            new Response(200, [], json_encode($this->orderPayload('ord-1', []))),
        ]);

        $updated = $client->salesOrders()->removeOrderItem('ord-1', 'item-1');

        self::assertInstanceOf(SalesOrderDTO::class, $updated);
        self::assertCount(0, $updated->orderItems);
    }

    public function test_remove_order_item_throws_not_found_when_item_id_missing(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('item-ghost');

        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$this->itemPayload('item-1')]))),
        ]);

        $client->salesOrders()->removeOrderItem('ord-1', 'item-ghost');
    }

    public function test_remove_order_item_propagates_optimistic_lock_exception(): void
    {
        $this->expectException(OptimisticLockException::class);

        $client = $this->makeClient([
            new Response(200, [], json_encode($this->orderPayload('ord-1', [$this->itemPayload('item-1')]))),
            new Response(409, [], json_encode(['error' => 'version conflict'])),
        ]);

        $client->salesOrders()->removeOrderItem('ord-1', 'item-1');
    }
}
