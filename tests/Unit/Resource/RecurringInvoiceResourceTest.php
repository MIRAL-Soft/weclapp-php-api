<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\RecurringInvoiceDTO;
use miralsoft\weclapp\api\DTO\RecurringInvoiceItemDTO;
use miralsoft\weclapp\api\Enum\RecurringInvoiceIntervalType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RecurringInvoiceResource and its DTOs.
 *
 * Fixtures mirror the live miralsoft tenant structure (recurringInvoice 16142,
 * MONTHLY, interval=1, 3 items).
 */
final class RecurringInvoiceResourceTest extends TestCase
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

    private function recurringInvoicePayload(string $id = '16142'): array
    {
        return [
            'id'                     => $id,
            'version'                => '5',
            'createdDate'            => 1576843563276,
            'lastModifiedDate'       => 1780281977110,
            'recurringInvoiceNumber' => '1001',
            'customerId'             => '9020',
            'interval'               => 1,
            'intervalType'           => 'MONTHLY',
            'intervalDayOfMonth'     => 1,
            'nextInvoiceDate'        => 1782856800000,
            'desiredInvoiceStatus'   => 'OPEN_ITEM_CREATED',
            'servicePeriodFromKey'   => 'LAST_RI_DATE_NEXT_START_OF_MONTH',
            'servicePeriodToKey'     => 'NEXT_RI_DATE_LESS1',
            'netAmount'              => '67',
            'grossAmount'            => '79.73',
            'sentToRecipient'        => true,
            'recurringInvoiceItems'  => [
                [
                    'id'             => '330203',
                    'version'        => '1',
                    'articleId'      => '307203',
                    'positionNumber' => 2,
                    'title'          => 'E-Mail-Archivierung',
                    'quantity'       => '1',
                    'unitId'         => '2220',
                    'unitPrice'      => '7.5',
                    'netAmount'      => '7.5',
                    'grossAmount'    => '8.93',
                    'taxId'          => '2181',
                    'itemType'       => 'DEFAULT',
                ],
            ],
            'customAttributes'       => [],
        ];
    }

    // ── DTO hydration & interval helpers ─────────────────────────────────────────

    public function test_from_array_hydrates_interval_fields(): void
    {
        $ri = RecurringInvoiceDTO::fromArray($this->recurringInvoicePayload());

        self::assertSame('1001', $ri->recurringInvoiceNumber);
        self::assertSame('9020', $ri->customerId);
        self::assertSame(1, $ri->interval);
        self::assertSame('MONTHLY', $ri->intervalType);
        self::assertSame(RecurringInvoiceIntervalType::Monthly, $ri->getIntervalType());
        self::assertSame(1, $ri->intervalDayOfMonth);
    }

    public function test_cadence_label(): void
    {
        $ri = RecurringInvoiceDTO::fromArray($this->recurringInvoicePayload());

        self::assertSame('every 1 MONTHLY', $ri->getCadenceLabel());
    }

    public function test_get_interval_type_returns_null_for_unmapped_value(): void
    {
        $payload = $this->recurringInvoicePayload();
        $payload['intervalType'] = 'FORTNIGHTLY'; // not in enum

        $ri = RecurringInvoiceDTO::fromArray($payload);

        self::assertNull($ri->getIntervalType());
        self::assertSame('FORTNIGHTLY', $ri->intervalType); // raw still available
    }

    public function test_next_invoice_date_helper(): void
    {
        $ri = RecurringInvoiceDTO::fromArray($this->recurringInvoicePayload());

        // Assert via timestamp to stay timezone-independent (epoch ms / 1000).
        self::assertSame(intdiv(1782856800000, 1000), $ri->getNextInvoiceDate()?->getTimestamp());
    }

    public function test_amounts_as_float(): void
    {
        $ri = RecurringInvoiceDTO::fromArray($this->recurringInvoicePayload());

        self::assertSame(67.0, $ri->getNetAmount());
        self::assertSame(79.73, $ri->getGrossAmount());
    }

    public function test_items_are_typed(): void
    {
        $ri = RecurringInvoiceDTO::fromArray($this->recurringInvoicePayload());

        self::assertCount(1, $ri->recurringInvoiceItems);
        $item = $ri->recurringInvoiceItems[0];
        self::assertInstanceOf(RecurringInvoiceItemDTO::class, $item);
        self::assertSame('307203', $item->articleId);
        self::assertSame(1.0, $item->getQuantity());
        self::assertSame(7.5, $item->getUnitPrice());
    }

    // ── Resource read operations ─────────────────────────────────────────────────

    public function test_find_returns_typed_dto(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->recurringInvoicePayload())),
        ]);

        $ri = $client->recurringInvoices()->find('16142');

        self::assertInstanceOf(RecurringInvoiceDTO::class, $ri);
        self::assertSame('1001', $ri->recurringInvoiceNumber);
    }

    public function test_find_by_customer_returns_list(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->recurringInvoicePayload()]])),
        ]);

        $list = $client->recurringInvoices()->findByCustomer('9020');

        self::assertCount(1, $list);
        self::assertSame('9020', $list[0]->customerId);
    }

    public function test_find_by_customer_returns_empty_for_empty_id_without_request(): void
    {
        $client = $this->makeClient([]); // no HTTP expected

        self::assertSame([], $client->recurringInvoices()->findByCustomer(''));
    }

    public function test_find_by_number_returns_match(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->recurringInvoicePayload()]])),
        ]);

        $ri = $client->recurringInvoices()->findByNumber('1001');

        self::assertNotNull($ri);
        self::assertSame('1001', $ri->recurringInvoiceNumber);
    }

    public function test_find_by_number_returns_null_when_missing(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        self::assertNull($client->recurringInvoices()->findByNumber('9999'));
    }

    public function test_find_modified_since_returns_list(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->recurringInvoicePayload()]])),
        ]);

        $list = $client->recurringInvoices()->findModifiedSince(1700000000000);

        self::assertCount(1, $list);
        self::assertContainsOnlyInstancesOf(RecurringInvoiceDTO::class, $list);
    }

    // ── Read-only guards ─────────────────────────────────────────────────────────

    public function test_create_throws_logic_exception(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('read-only');

        $client = $this->makeClient([]);
        $client->recurringInvoices()->create(['customerId' => '9020']);
    }

    public function test_update_throws_logic_exception(): void
    {
        $this->expectException(\LogicException::class);

        $client = $this->makeClient([]);
        $client->recurringInvoices()->update('16142', ['interval' => 2]);
    }

    public function test_delete_throws_logic_exception(): void
    {
        $this->expectException(\LogicException::class);

        $client = $this->makeClient([]);
        $client->recurringInvoices()->delete('16142');
    }

    public function test_client_exposes_recurring_invoices_method(): void
    {
        $config = new WeclappConfig(tenant: 'test', token: 'test-token');

        self::assertTrue(method_exists(new WeclappClient($config), 'recurringInvoices'));
    }
}
