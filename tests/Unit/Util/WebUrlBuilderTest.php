<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Util;

use InvalidArgumentException;
use miralsoft\weclapp\api\Util\WebUrlBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WebUrlBuilder.
 *
 * The expected URLs match the live-verified browser links (miralsoft tenant,
 * 2026-06-12): /app/sales-invoice/1000372 and /app/sales-order/488081, where
 * the trailing number is the API entity id.
 */
final class WebUrlBuilderTest extends TestCase
{
    private const BASE = 'https://miralsoft.weclapp.com/';

    public function test_builds_sales_order_url(): void
    {
        self::assertSame(
            'https://miralsoft.weclapp.com/app/sales-order/488081',
            WebUrlBuilder::build(self::BASE, 'salesOrder', '488081'),
        );
    }

    public function test_builds_sales_invoice_url(): void
    {
        self::assertSame(
            'https://miralsoft.weclapp.com/app/sales-invoice/1000372',
            WebUrlBuilder::build(self::BASE, 'salesInvoice', '1000372'),
        );
    }

    public function test_base_url_without_trailing_slash_is_handled(): void
    {
        self::assertSame(
            'https://miralsoft.weclapp.com/app/sales-order/1',
            WebUrlBuilder::build('https://miralsoft.weclapp.com', 'salesOrder', '1'),
        );
    }

    public function test_id_is_url_encoded(): void
    {
        self::assertSame(
            'https://miralsoft.weclapp.com/app/sales-order/a%2Fb',
            WebUrlBuilder::build(self::BASE, 'salesOrder', 'a/b'),
        );
    }

    public function test_unknown_entity_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('article');

        WebUrlBuilder::build(self::BASE, 'article', '123');
    }

    public function test_empty_id_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WebUrlBuilder::build(self::BASE, 'salesOrder', '');
    }

    public function test_is_supported(): void
    {
        self::assertTrue(WebUrlBuilder::isSupported('salesOrder'));
        self::assertTrue(WebUrlBuilder::isSupported('salesInvoice'));
        self::assertFalse(WebUrlBuilder::isSupported('article'));
    }

    public function test_supported_entities(): void
    {
        self::assertSame(['salesOrder', 'salesInvoice'], WebUrlBuilder::supportedEntities());
    }
}
