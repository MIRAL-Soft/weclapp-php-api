<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\CustomAttributeDTO;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for custom attribute value read/write through the entity resource.
 *
 * Covers AbstractResource::setCustomAttribute() (Read-Modify-Write) and the
 * AbstractDTO::getCustomAttribute()/getCustomAttributeValue() readers, exercised
 * via SalesOrderResource / SalesOrderDTO.
 */
final class CustomAttributeValueIntegrationTest extends TestCase
{
    /** @param list<Response> $responses */
    private function makeClient(array $responses): WeclappClient
    {
        $mock   = new MockHandler($responses);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);

        return new WeclappClient(
            new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0),
            guzzle: $guzzle,
        );
    }

    private function orderPayload(array $customAttributes = []): array
    {
        return [
            'id'               => 'ord-1',
            'version'          => '3',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'orderNumber'      => 'SO-10042',
            'status'           => 'ORDER_CONFIRMED',
            'customerId'       => 'cust-1',
            'orderDate'        => 1711400000000,
            'orderItems'       => [],
            'tags'             => [],
            'statusHistory'    => [],
            'customAttributes' => $customAttributes,
        ];
    }

    // ── DTO readers ─────────────────────────────────────────────────────────────

    public function test_get_custom_attribute_returns_dto(): void
    {
        $order = SalesOrderDTO::fromArray($this->orderPayload([
            ['attributeDefinitionId' => '998852', 'stringValue' => 'TICKET-4711'],
        ]));

        $attr = $order->getCustomAttribute('998852');

        self::assertInstanceOf(CustomAttributeDTO::class, $attr);
        self::assertSame('TICKET-4711', $attr->stringValue);
    }

    public function test_get_custom_attribute_value_returns_scalar(): void
    {
        $order = SalesOrderDTO::fromArray($this->orderPayload([
            ['attributeDefinitionId' => '998852', 'stringValue' => 'TICKET-4711'],
        ]));

        self::assertSame('TICKET-4711', $order->getCustomAttributeValue('998852'));
    }

    public function test_get_custom_attribute_returns_null_when_absent(): void
    {
        $order = SalesOrderDTO::fromArray($this->orderPayload([]));

        self::assertNull($order->getCustomAttribute('998852'));
        self::assertNull($order->getCustomAttributeValue('998852'));
    }

    // ── setCustomAttribute (Read-Modify-Write) ──────────────────────────────────

    public function test_set_custom_attribute_appends_when_absent(): void
    {
        $captured = null;

        $client = $this->makeClient([
            // GET (findRaw) — no custom attributes yet
            new Response(200, [], json_encode($this->orderPayload([]))),
            // PUT (update) — echo back what was sent (captured via the next read)
            new Response(200, [], json_encode($this->orderPayload([
                ['attributeDefinitionId' => '998852', 'stringValue' => 'TICKET-4711'],
            ]))),
        ]);

        $result = $client->salesOrders()->setCustomAttribute(
            'ord-1',
            CustomAttributeDTO::string('998852', 'TICKET-4711'),
        );

        self::assertSame('TICKET-4711', $result->getCustomAttributeValue('998852'));
    }

    public function test_set_custom_attribute_replaces_existing(): void
    {
        $client = $this->makeClient([
            // GET (findRaw) — existing value
            new Response(200, [], json_encode($this->orderPayload([
                ['attributeDefinitionId' => '998852', 'stringValue' => 'OLD'],
                ['attributeDefinitionId' => 'other', 'stringValue' => 'KEEP'],
            ]))),
            // PUT (update) — server returns the merged record
            new Response(200, [], json_encode($this->orderPayload([
                ['attributeDefinitionId' => '998852', 'stringValue' => 'NEW'],
                ['attributeDefinitionId' => 'other', 'stringValue' => 'KEEP'],
            ]))),
        ]);

        $result = $client->salesOrders()->setCustomAttribute(
            'ord-1',
            CustomAttributeDTO::string('998852', 'NEW'),
        );

        self::assertSame('NEW', $result->getCustomAttributeValue('998852'));
        self::assertSame('KEEP', $result->getCustomAttributeValue('other'));
    }

    public function test_set_custom_attribute_throws_without_definition_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = $this->makeClient([]); // no HTTP calls expected

        $client->salesOrders()->setCustomAttribute('ord-1', ['stringValue' => 'oops']);
    }
}
