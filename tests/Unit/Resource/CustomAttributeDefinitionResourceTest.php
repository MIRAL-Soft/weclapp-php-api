<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\CustomAttributeDefinitionDTO;
use miralsoft\weclapp\api\Enum\CustomAttributeEntityType;
use miralsoft\weclapp\api\Enum\CustomAttributeType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CustomAttributeDefinitionResource.
 *
 * Fixture values mirror the live miralsoft tenant response (definition
 * "docbeeTicketId", id=998852, STRING, entities=["salesOrder"]).
 */
final class CustomAttributeDefinitionResourceTest extends TestCase
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

    private function definitionPayload(
        string $id = '998852',
        string $key = 'docbeeTicketId',
        string $type = 'STRING',
        array  $entities = ['salesOrder'],
    ): array {
        return [
            'id'                    => $id,
            'version'               => '1',
            'createdDate'           => 1700000000000,
            'lastModifiedDate'      => 1700000000000,
            'attributeKey'          => $key,
            'label'                 => 'Docbee Ticket ID',
            'attributeType'         => $type,
            'entities'              => $entities,
            'active'                => true,
            'mandatory'             => false,
            'readOnly'              => false,
            'systemCustomAttribute' => false,
            'selectableValues'      => [],
        ];
    }

    // ── DTO ───────────────────────────────────────────────────────────────────

    public function test_from_array_hydrates_definition(): void
    {
        $dto = CustomAttributeDefinitionDTO::fromArray($this->definitionPayload());

        self::assertSame('998852', $dto->id);
        self::assertSame('docbeeTicketId', $dto->attributeKey);
        self::assertSame('STRING', $dto->attributeType);
        self::assertSame(CustomAttributeType::String, $dto->getType());
        self::assertSame(['salesOrder'], $dto->entities);
    }

    public function test_applies_to_returns_true_for_matching_entity(): void
    {
        $dto = CustomAttributeDefinitionDTO::fromArray($this->definitionPayload());

        self::assertTrue($dto->appliesTo('salesOrder'));
        self::assertFalse($dto->appliesTo('article'));
    }

    // ── find / create ──────────────────────────────────────────────────────────

    public function test_find_returns_definition_dto(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->definitionPayload())),
        ]);

        $def = $client->customAttributeDefinitions()->find('998852');

        self::assertInstanceOf(CustomAttributeDefinitionDTO::class, $def);
        self::assertSame('docbeeTicketId', $def->attributeKey);
    }

    public function test_find_by_entity_filters_client_side(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [
                $this->definitionPayload('1', 'keyA', 'STRING', ['salesOrder']),
                $this->definitionPayload('2', 'keyB', 'STRING', ['article']),
                $this->definitionPayload('3', 'keyC', 'STRING', ['salesOrder', 'quotation']),
            ]])),
        ]);

        $defs = $client->customAttributeDefinitions()->findByEntity(CustomAttributeEntityType::SalesOrder);

        self::assertCount(2, $defs);
        self::assertSame(['1', '3'], array_map(static fn ($d) => $d->id, $defs));
    }

    public function test_find_by_key_returns_match(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->definitionPayload()]])),
        ]);

        $def = $client->customAttributeDefinitions()->findByKey('docbeeTicketId', CustomAttributeEntityType::SalesOrder);

        self::assertNotNull($def);
        self::assertSame('998852', $def->id);
    }

    public function test_find_by_key_returns_null_when_entity_does_not_match(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [
                $this->definitionPayload('998852', 'docbeeTicketId', 'STRING', ['article']),
            ]])),
        ]);

        $def = $client->customAttributeDefinitions()->findByKey('docbeeTicketId', CustomAttributeEntityType::SalesOrder);

        self::assertNull($def);
    }

    // ── ensure idempotency ──────────────────────────────────────────────────────

    public function test_ensure_returns_existing_without_creating(): void
    {
        // Only ONE response queued (the listAll lookup). If ensure tried to POST,
        // the MockHandler would throw "no more responses" → test fails.
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->definitionPayload()]])),
        ]);

        $def = $client->customAttributeDefinitions()->ensure(
            CustomAttributeEntityType::SalesOrder,
            'docbeeTicketId',
            'Docbee Ticket ID',
            CustomAttributeType::String,
        );

        self::assertSame('998852', $def->id);
    }

    public function test_ensure_creates_when_absent(): void
    {
        $client = $this->makeClient([
            // 1) lookup → empty
            new Response(200, [], json_encode(['result' => []])),
            // 2) create → returns the new definition
            new Response(201, [], json_encode($this->definitionPayload('new-1', 'docbeeTicketId'))),
        ]);

        $def = $client->customAttributeDefinitions()->ensure(
            CustomAttributeEntityType::SalesOrder,
            'docbeeTicketId',
            'Docbee Ticket ID',
        );

        self::assertSame('new-1', $def->id);
        self::assertSame('docbeeTicketId', $def->attributeKey);
    }

    public function test_client_exposes_custom_attribute_definitions_method(): void
    {
        $config = new WeclappConfig(tenant: 'test', token: 'test-token');

        self::assertTrue(method_exists(new WeclappClient($config), 'customAttributeDefinitions'));
    }
}
