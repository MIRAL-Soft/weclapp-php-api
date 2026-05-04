<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\CustomerDTO;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use miralsoft\weclapp\api\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for dry-run mode (AbstractResource::withDryRun()).
 *
 * Tests verify:
 *  - withDryRun() returns a clone and does not mutate the original resource.
 *  - The ?dryRun=true query parameter is appended to POST, PUT, and DELETE URLs.
 *  - Normal (non-dry-run) calls do NOT get the parameter.
 *  - Dry-run responses (HTTP 200 with partial body) are parsed into DTOs correctly.
 *  - Validation errors (HTTP 400) propagate identically to real calls.
 */
class DryRunTest extends TestCase
{
    /**
     * Build a WeclappClient backed by a MockHandler and capture all outgoing
     * requests in $container for URL inspection.
     *
     * @param list<Response>  $responses Queued mock responses.
     * @param list<array>    &$container Populated by Guzzle's history middleware.
     */
    private function makeClient(array $responses, array &$container): WeclappClient
    {
        $history = Middleware::history($container);
        $stack   = HandlerStack::create(new MockHandler($responses));
        $stack->push($history);

        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

        return new WeclappClient(
            new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0),
            guzzle: $guzzle,
        );
    }

    /** Minimal payload that satisfies SalesOrderDTO::fromArray(). */
    private function orderPayload(string $id = 'ord-1'): array
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
            'orderItems'       => [],
            'tags'             => [],
            'customAttributes' => [],
        ];
    }

    /** Minimal payload that satisfies CustomerDTO::fromArray(). */
    private function customerPayload(string $id = 'cust-1'): array
    {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'customerNumber'   => 'K-10001',
            'partyType'        => 'ORGANIZATION',
            'company'          => 'ACME GmbH',
            'tags'             => [],
            'customAttributes' => [],
        ];
    }

    // -------------------------------------------------------------------------
    // Clone behaviour
    // -------------------------------------------------------------------------

    public function test_with_dry_run_returns_new_instance(): void
    {
        $container = [];
        $client    = $this->makeClient([], $container);
        $resource  = $client->salesOrders();
        $dryClone  = $resource->withDryRun();

        self::assertNotSame($resource, $dryClone, 'withDryRun() must return a new instance.');
    }

    public function test_with_dry_run_does_not_mutate_original(): void
    {
        $container = [];
        $client    = $this->makeClient([], $container);
        $resource  = $client->salesOrders();
        $resource->withDryRun(); // discard the clone

        self::assertFalse($resource->isDryRun(), 'Original resource must remain unmodified.');
    }

    public function test_with_dry_run_sets_flag_on_clone(): void
    {
        $container = [];
        $client    = $this->makeClient([], $container);

        $dryClone = $client->salesOrders()->withDryRun();

        self::assertTrue($dryClone->isDryRun());
    }

    public function test_with_dry_run_false_disables_flag(): void
    {
        $container = [];
        $client    = $this->makeClient([], $container);

        $dryClone     = $client->salesOrders()->withDryRun();
        $normalClone  = $dryClone->withDryRun(false);

        self::assertTrue($dryClone->isDryRun());
        self::assertFalse($normalClone->isDryRun());
    }

    // -------------------------------------------------------------------------
    // POST (create) — query parameter
    // -------------------------------------------------------------------------

    public function test_create_dry_run_appends_dryrun_param_to_url(): void
    {
        $container = [];
        // Dry-run POST returns 200 (not 201)
        $client = $this->makeClient(
            [new Response(200, [], json_encode($this->orderPayload()))],
            $container,
        );

        $client->salesOrders()->withDryRun()->create(['customerId' => 'cust-1']);

        self::assertCount(1, $container);
        $uri = (string) $container[0]['request']->getUri();
        self::assertStringContainsString('dryRun=true', $uri);
    }

    public function test_regular_create_does_not_append_dryrun_param(): void
    {
        $container = [];
        $client    = $this->makeClient(
            [new Response(201, [], json_encode($this->orderPayload()))],
            $container,
        );

        $client->salesOrders()->create(['customerId' => 'cust-1']);

        $uri = (string) $container[0]['request']->getUri();
        self::assertStringNotContainsString('dryRun', $uri);
    }

    public function test_create_dry_run_returns_dto_from_200_response(): void
    {
        // The API omits id / version / createdDate / lastModifiedDate in dry-run mode
        $dryPayload = [
            'orderNumber'      => 'SO-99999',
            'status'           => 'ORDER_ENTRY_IN_PROGRESS',
            'customerId'       => 'cust-1',
            'orderDate'        => 1711400000000,
            'orderItems'       => [],
            'tags'             => [],
            'customAttributes' => [],
        ];

        $container = [];
        $client    = $this->makeClient(
            [new Response(200, [], json_encode($dryPayload))],
            $container,
        );

        $order = $client->salesOrders()->withDryRun()->create(['customerId' => 'cust-1']);

        self::assertInstanceOf(SalesOrderDTO::class, $order);
        // DTOs use '' (empty string) as default for missing string fields — see AbstractDTO::str()
        self::assertSame('', $order->id, 'id must be empty — not returned by dry-run.');
        self::assertSame('', $order->version, 'version must be empty — not returned by dry-run.');
        self::assertSame('SO-99999', $order->orderNumber);
        self::assertSame('ORDER_ENTRY_IN_PROGRESS', $order->status);
    }

    public function test_create_dry_run_throws_validation_exception_on_400(): void
    {
        $this->expectException(ValidationException::class);

        $errorPayload = [
            'message'          => 'Validation failed',
            'validationErrors' => [['field' => 'customerId', 'message' => 'required']],
        ];

        $container = [];
        $client    = $this->makeClient(
            [new Response(400, [], json_encode($errorPayload))],
            $container,
        );

        $client->salesOrders()->withDryRun()->create([]);
    }

    // -------------------------------------------------------------------------
    // PUT (update) — query parameter
    // -------------------------------------------------------------------------

    public function test_update_dry_run_appends_dryrun_param_to_url(): void
    {
        $container = [];
        $client    = $this->makeClient(
            [new Response(200, [], json_encode($this->customerPayload()))],
            $container,
        );

        $client->customers()->withDryRun()->update('cust-1', ['company' => 'New Name GmbH']);

        self::assertCount(1, $container);
        $uri = (string) $container[0]['request']->getUri();
        self::assertStringContainsString('dryRun=true', $uri);
    }

    public function test_regular_update_does_not_append_dryrun_param(): void
    {
        $container = [];
        $client    = $this->makeClient(
            [new Response(200, [], json_encode($this->customerPayload()))],
            $container,
        );

        $client->customers()->update('cust-1', ['company' => 'New Name GmbH']);

        $uri = (string) $container[0]['request']->getUri();
        self::assertStringNotContainsString('dryRun', $uri);
    }

    public function test_update_dry_run_returns_partial_dto(): void
    {
        $dryPayload = [
            // id / version / dates omitted by weclapp in dry-run
            'customerNumber'   => 'K-10001',
            'partyType'        => 'ORGANIZATION',
            'company'          => 'Dry-Run GmbH',
            'tags'             => [],
            'customAttributes' => [],
        ];

        $container = [];
        $client    = $this->makeClient(
            [new Response(200, [], json_encode($dryPayload))],
            $container,
        );

        $customer = $client->customers()->withDryRun()->update('cust-1', ['company' => 'Dry-Run GmbH']);

        self::assertInstanceOf(CustomerDTO::class, $customer);
        // DTOs use '' as default for missing string fields — see AbstractDTO::str()
        self::assertSame('', $customer->id, 'id must be empty — not returned by dry-run.');
        self::assertSame('Dry-Run GmbH', $customer->company);
    }

    // -------------------------------------------------------------------------
    // DELETE — query parameter
    // -------------------------------------------------------------------------

    public function test_delete_dry_run_appends_dryrun_param_to_url(): void
    {
        $container = [];
        // Dry-run DELETE returns 200 (instead of 204)
        $client    = $this->makeClient(
            [new Response(200, [], '{}')],
            $container,
        );

        $client->salesOrders()->withDryRun()->delete('ord-1');

        self::assertCount(1, $container);
        $uri = (string) $container[0]['request']->getUri();
        self::assertStringContainsString('dryRun=true', $uri);
    }

    public function test_regular_delete_does_not_append_dryrun_param(): void
    {
        $container = [];
        $client    = $this->makeClient(
            [new Response(204, [], '')],
            $container,
        );

        $client->salesOrders()->delete('ord-1');

        $uri = (string) $container[0]['request']->getUri();
        self::assertStringNotContainsString('dryRun', $uri);
    }

    public function test_delete_dry_run_returns_void_on_200(): void
    {
        $container = [];
        $client    = $this->makeClient(
            [new Response(200, [], '{}')],
            $container,
        );

        // Must not throw and must return void
        $result = $client->salesOrders()->withDryRun()->delete('ord-1');

        self::assertNull($result);
    }
}
