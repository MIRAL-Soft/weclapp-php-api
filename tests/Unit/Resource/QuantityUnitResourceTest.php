<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\QuantityUnitDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QuantityUnitResource and QuantityUnitDTO.
 *
 * Fixture values are derived from the live miralsoft tenant response (2025-05-27):
 *   Stunde (h): id=2221, timeUnitAmount=3600 (seconds)
 *   Stk.:       id=2220, timeUnitAmount=null
 */
class QuantityUnitResourceTest extends TestCase
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

    /** Build a unit payload matching the live API structure. */
    private function unitPayload(
        string  $id,
        string  $name,
        ?string $description = null,
        ?int    $timeUnitAmount = null,
    ): array {
        $data = [
            'id'               => $id,
            'version'          => '0',
            'createdDate'      => 1576686544811,
            'lastModifiedDate' => 1576686544811,
            'name'             => $name,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        if ($timeUnitAmount !== null) {
            $data['timeUnitAmount'] = $timeUnitAmount;
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // DTO hydration
    // -------------------------------------------------------------------------

    public function test_from_array_hydrates_time_unit(): void
    {
        $dto = QuantityUnitDTO::fromArray(
            $this->unitPayload('2221', 'h', 'Stunde', 3600)
        );

        self::assertSame('2221', $dto->id);
        self::assertSame('h', $dto->name);
        self::assertSame('Stunde', $dto->description);
        self::assertSame(3600, $dto->timeUnitAmount);
    }

    public function test_from_array_hydrates_quantity_unit_without_time_amount(): void
    {
        $dto = QuantityUnitDTO::fromArray(
            $this->unitPayload('2220', 'Stk.', 'Stück')
        );

        self::assertSame('2220', $dto->id);
        self::assertSame('Stk.', $dto->name);
        self::assertNull($dto->timeUnitAmount);
    }

    public function test_from_array_handles_absent_description(): void
    {
        $dto = QuantityUnitDTO::fromArray(
            $this->unitPayload('9458', 'Jahr')
        );

        self::assertNull($dto->description);
        self::assertNull($dto->timeUnitAmount);
    }

    // -------------------------------------------------------------------------
    // isTimeUnit() / getMilliseconds()
    // -------------------------------------------------------------------------

    public function test_is_time_unit_returns_true_when_time_unit_amount_present(): void
    {
        $dto = QuantityUnitDTO::fromArray($this->unitPayload('2221', 'h', 'Stunde', 3600));

        self::assertTrue($dto->isTimeUnit());
    }

    public function test_is_time_unit_returns_false_when_time_unit_amount_absent(): void
    {
        $dto = QuantityUnitDTO::fromArray($this->unitPayload('2220', 'Stk.', 'Stück'));

        self::assertFalse($dto->isTimeUnit());
    }

    public function test_get_milliseconds_converts_seconds_to_ms(): void
    {
        $dto = QuantityUnitDTO::fromArray($this->unitPayload('2221', 'h', 'Stunde', 3600));

        // 3600 s × 1000 = 3_600_000 ms (1 hour)
        self::assertSame(3_600_000, $dto->getMilliseconds());
    }

    public function test_get_milliseconds_returns_null_for_non_time_unit(): void
    {
        $dto = QuantityUnitDTO::fromArray($this->unitPayload('2220', 'Stk.', 'Stück'));

        self::assertNull($dto->getMilliseconds());
    }

    // -------------------------------------------------------------------------
    // Resource: find()
    // -------------------------------------------------------------------------

    public function test_find_returns_quantity_unit_dto(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->unitPayload('2221', 'h', 'Stunde', 3600))),
        ]);

        $unit = $client->quantityUnits()->find('2221');

        self::assertInstanceOf(QuantityUnitDTO::class, $unit);
        self::assertSame('2221', $unit->id);
        self::assertSame('h', $unit->name);
        self::assertSame(3600, $unit->timeUnitAmount);
    }

    // -------------------------------------------------------------------------
    // Resource: findByName()
    // -------------------------------------------------------------------------

    public function test_find_by_name_returns_matching_unit(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [
                $this->unitPayload('2221', 'h', 'Stunde', 3600),
            ]])),
        ]);

        $unit = $client->quantityUnits()->findByName('h');

        self::assertSame('h', $unit->name);
        self::assertSame(3600, $unit->timeUnitAmount);
    }

    public function test_find_by_name_throws_not_found_for_unknown_name(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $client->quantityUnits()->findByName('NonExistentUnit');
    }

    // -------------------------------------------------------------------------
    // Resource: findTimeUnits()
    // -------------------------------------------------------------------------

    public function test_find_time_units_returns_only_units_with_time_unit_amount(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [
                $this->unitPayload('2221', 'h',    'Stunde', 3600),
                $this->unitPayload('2220', 'Stk.', 'Stück'),        // no timeUnitAmount
                $this->unitPayload('9458', 'Jahr'),                  // no timeUnitAmount
                $this->unitPayload('9459', 'Monat'),                 // no timeUnitAmount
            ]])),
        ]);

        $timeUnits = $client->quantityUnits()->findTimeUnits();

        self::assertCount(1, $timeUnits);
        self::assertSame('h', $timeUnits[0]->name);
    }

    public function test_find_time_units_returns_empty_array_when_none_present(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [
                $this->unitPayload('2220', 'Stk.', 'Stück'),
            ]])),
        ]);

        $timeUnits = $client->quantityUnits()->findTimeUnits();

        self::assertSame([], $timeUnits);
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function test_create_unit(): void
    {
        $client = $this->makeClient([
            new Response(201, [], json_encode($this->unitPayload('new-1', 'min', 'Minute', 60))),
        ]);

        $unit = $client->quantityUnits()->create(['name' => 'min', 'description' => 'Minute', 'timeUnitAmount' => 60]);

        self::assertSame('new-1', $unit->id);
        self::assertSame('min', $unit->name);
        self::assertSame(60, $unit->timeUnitAmount);
        self::assertSame(60_000, $unit->getMilliseconds());
    }

    public function test_delete_unit(): void
    {
        $client = $this->makeClient([new Response(204)]);

        $client->quantityUnits()->delete('2221');
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // method_exists() guard — Docbee fail-soft check
    // -------------------------------------------------------------------------

    public function test_client_exposes_quantity_units_method(): void
    {
        $config = new WeclappConfig(tenant: 'test', token: 'test-token');

        self::assertTrue(method_exists(new WeclappClient($config), 'quantityUnits'));
    }
}
