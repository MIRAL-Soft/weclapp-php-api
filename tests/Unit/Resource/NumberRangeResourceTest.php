<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\NumberRangeDTO;
use miralsoft\weclapp\api\DTO\NumberRangeValueDTO;
use miralsoft\weclapp\api\Enum\NumberRangeType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NumberRangeResource and NumberRangeValueResource.
 */
class NumberRangeResourceTest extends TestCase
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

    private function rangePayload(string $id = 'nr-1', string $type = 'PROFORMA_INVOICE'): array
    {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'type'             => $type,
        ];
    }

    private function rangeValuePayload(
        string  $numberRangeId = 'nr-1',
        string  $prefix = 'PR-',
        ?int    $validFromDate = null,
        ?int    $validToDate = null,
    ): array {
        return [
            'id'                     => 'nrv-1',
            'version'                => '1',
            'createdDate'            => 1711400000000,
            'lastModifiedDate'       => 1711450000000,
            'numberRangeId'          => $numberRangeId,
            'interval'               => 1,
            'lastValue'              => 41,
            'length'                 => 4,
            'prefix'                 => $prefix,
            'suffix'                 => null,
            'validFromDate'          => $validFromDate,
            'validToDate'            => $validToDate,
            'salesInvoiceTypes'      => [],
            'creditNoteInvoiceTypes' => [],
            'salesChannels'          => [],
            'articleCategories'      => [],
        ];
    }

    // -------------------------------------------------------------------------
    // NumberRangeResource tests
    // -------------------------------------------------------------------------

    public function test_find_returns_number_range_dto(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->rangePayload())),
        ]);

        $range = $client->numberRanges()->find('nr-1');

        self::assertInstanceOf(NumberRangeDTO::class, $range);
        self::assertSame('nr-1', $range->id);
        self::assertSame('PROFORMA_INVOICE', $range->type);
        self::assertSame(NumberRangeType::ProformaInvoice, $range->getType());
    }

    public function test_find_by_type_returns_matching_range(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->rangePayload()]])),
        ]);

        $range = $client->numberRanges()->findByType(NumberRangeType::ProformaInvoice);

        self::assertInstanceOf(NumberRangeDTO::class, $range);
        self::assertSame('PROFORMA_INVOICE', $range->type);
    }

    public function test_find_by_type_accepts_string(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->rangePayload('nr-2', 'SALES_INVOICE')]])),
        ]);

        $range = $client->numberRanges()->findByType('SALES_INVOICE');

        self::assertSame('SALES_INVOICE', $range->type);
    }

    public function test_find_by_type_returns_null_when_not_configured(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $range = $client->numberRanges()->findByType(NumberRangeType::ProformaInvoice);

        self::assertNull($range);
    }

    public function test_get_proforma_invoice_prefix_returns_configured_prefix(): void
    {
        // First call: find the PROFORMA_INVOICE number range
        // Second call: find its values
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->rangePayload('nr-proforma')]])),
            new Response(200, [], json_encode(['result' => [$this->rangeValuePayload('nr-proforma', 'PR-')]])),
        ]);

        $prefix = $client->numberRanges()->getProformaInvoicePrefix();

        self::assertSame('PR-', $prefix);
    }

    public function test_get_proforma_invoice_prefix_returns_null_when_no_range(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $prefix = $client->numberRanges()->getProformaInvoicePrefix();

        self::assertNull($prefix);
    }

    public function test_get_proforma_invoice_prefix_returns_null_when_no_values(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->rangePayload('nr-proforma')]])),
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $prefix = $client->numberRanges()->getProformaInvoicePrefix();

        self::assertNull($prefix);
    }

    // -------------------------------------------------------------------------
    // NumberRangeValueResource tests
    // -------------------------------------------------------------------------

    public function test_find_returns_number_range_value_dto(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->rangeValuePayload())),
        ]);

        $value = $client->numberRangeValues()->find('nrv-1');

        self::assertInstanceOf(NumberRangeValueDTO::class, $value);
        self::assertSame('PR-', $value->prefix);
        self::assertSame('nr-1', $value->numberRangeId);
    }

    public function test_find_by_number_range_returns_list(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->rangeValuePayload()]])),
        ]);

        $values = $client->numberRangeValues()->findByNumberRange('nr-1');

        self::assertCount(1, $values);
        self::assertInstanceOf(NumberRangeValueDTO::class, $values[0]);
    }

    public function test_find_prefix_prefers_active_value(): void
    {
        $nowMs   = (int) (microtime(true) * 1000);
        $pastMs  = $nowMs - 86_400_000 * 365; // 1 year ago
        $futureMs = $nowMs + 86_400_000 * 365; // 1 year from now

        // Two values: first is expired, second is active
        $expired = $this->rangeValuePayload('nr-1', 'OLD-', 0, $pastMs);
        $active  = $this->rangeValuePayload('nr-1', 'PR-', $pastMs, $futureMs);

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$expired, $active]])),
        ]);

        $prefix = $client->numberRangeValues()->findPrefix('nr-1');

        self::assertSame('PR-', $prefix);
    }

    public function test_number_range_type_enum_covers_proforma(): void
    {
        self::assertSame('PROFORMA_INVOICE', NumberRangeType::ProformaInvoice->value);
    }

    public function test_number_range_dto_get_type_returns_null_for_unknown(): void
    {
        $dto = NumberRangeDTO::fromArray([
            'id' => 'x', 'version' => '1',
            'createdDate' => 0, 'lastModifiedDate' => 0,
            'type' => 'UNKNOWN_FUTURE_TYPE',
        ]);

        self::assertNull($dto->getType());
    }
}
