<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the web-URL deep-link surface on the client and resources.
 *
 * No HTTP is involved — webUrl() is a pure builder, so the MockHandler stays
 * empty (any request would make it throw).
 */
final class WebUrlResourceTest extends TestCase
{
    private function client(string $tenant = 'miralsoft'): WeclappClient
    {
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create(new MockHandler([])), 'http_errors' => false]);

        return new WeclappClient(
            new WeclappConfig(tenant: $tenant, token: 'test-token', maxRetries: 0),
            guzzle: $guzzle,
        );
    }

    public function test_config_web_base_url(): void
    {
        self::assertSame(
            'https://miralsoft.weclapp.com/',
            (new WeclappConfig(tenant: 'miralsoft', token: 't'))->getWebBaseUrl(),
        );
    }

    public function test_generic_client_web_url(): void
    {
        self::assertSame(
            'https://miralsoft.weclapp.com/app/sales-order/488081',
            $this->client()->webUrl('salesOrder', '488081'),
        );
    }

    public function test_sales_order_resource_web_url(): void
    {
        self::assertSame(
            'https://miralsoft.weclapp.com/app/sales-order/488081',
            $this->client()->salesOrders()->webUrl('488081'),
        );
    }

    public function test_sales_invoice_resource_web_url(): void
    {
        self::assertSame(
            'https://miralsoft.weclapp.com/app/sales-invoice/1000372',
            $this->client()->salesInvoices()->webUrl('1000372'),
        );
    }

    public function test_web_url_is_tenant_aware(): void
    {
        self::assertSame(
            'https://acme.weclapp.com/app/sales-order/5',
            $this->client('acme')->salesOrders()->webUrl('5'),
        );
    }

    public function test_generic_web_url_rejects_unknown_entity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->client()->webUrl('article', '123');
    }
}
