<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\CustomerDTO;
use miralsoft\weclapp\api\DTO\PaginatedResultDTO;
use miralsoft\weclapp\api\Exception\AuthenticationException;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\RateLimitException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CustomerResource using a mocked HTTP client.
 */
class CustomerResourceTest extends TestCase
{
    private function makeConfig(): WeclappConfig
    {
        return new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0);
    }

    /**
     * Build a WeclappClient backed by a Guzzle MockHandler.
     *
     * @param list<Response> $responses Queued HTTP responses in order.
     */
    private function makeClient(array $responses): WeclappClient
    {
        $mock    = new MockHandler($responses);
        $guzzle  = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);

        return new WeclappClient($this->makeConfig(), guzzle: $guzzle);
    }

    private function customerPayload(string $id = 'cust-1', string $number = 'K-100'): array
    {
        return [
            'id'              => $id,
            'version'         => '1',
            'createdDate'     => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'customerNumber'  => $number,
            'partyType'       => 'ORGANIZATION',
            'company'         => 'Test GmbH',
            'active'          => true,
            'blocked'         => false,
            'insolvent'       => false,
            'addresses'       => [],
            'contacts'        => [],
            'tags'            => [],
            'customAttributes' => [],
        ];
    }

    // -------------------------------------------------------------------------
    // count()
    // -------------------------------------------------------------------------

    public function test_count_returns_integer(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['count' => 42])),
        ]);

        self::assertSame(42, $client->customers()->count());
    }

    // -------------------------------------------------------------------------
    // find()
    // -------------------------------------------------------------------------

    public function test_find_returns_customer_dto(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->customerPayload())),
        ]);

        $customer = $client->customers()->find('cust-1');

        self::assertInstanceOf(CustomerDTO::class, $customer);
        self::assertSame('cust-1', $customer->id);
        self::assertSame('Test GmbH', $customer->company);
    }

    public function test_find_throws_not_found_on_404(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([
            new Response(404, [], json_encode(['message' => 'Not found'])),
        ]);

        $client->customers()->find('non-existent');
    }

    public function test_find_throws_authentication_exception_on_401(): void
    {
        $this->expectException(AuthenticationException::class);

        $client = $this->makeClient([
            new Response(401, [], '{"message":"Unauthorized"}'),
        ]);

        $client->customers()->find('any-id');
    }

    // -------------------------------------------------------------------------
    // list()
    // -------------------------------------------------------------------------

    public function test_list_returns_paginated_result(): void
    {
        $body = json_encode([
            'result'      => [$this->customerPayload('c1'), $this->customerPayload('c2', 'K-101')],
            'recordCount' => 2,
        ]);

        $client = $this->makeClient([new Response(200, [], $body)]);
        $result = $client->customers()->list();

        self::assertInstanceOf(PaginatedResultDTO::class, $result);
        self::assertCount(2, $result->items);
        self::assertInstanceOf(CustomerDTO::class, $result->items[0]);
        self::assertSame('c1', $result->items[0]->id);
    }

    public function test_list_has_more_when_items_fill_page(): void
    {
        // Return 50 items (fills the default page size of 50) → hasMore should be true
        $items = array_map(
            fn (int $i): array => $this->customerPayload('c' . $i, 'K-' . $i),
            range(1, 50)
        );

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => $items])),
        ]);

        $result = $client->customers()->list();

        self::assertTrue($result->hasMore);
    }

    public function test_find_by_company_paginates_beyond_one_page(): void
    {
        // Regression test: findByCompany() previously used list() with the default
        // pageSize of 50 and silently truncated larger result sets. It must now
        // paginate through ALL pages (listAll, pageSize 1000).
        $fullPage = array_map(
            fn (int $i): array => $this->customerPayload('c' . $i, 'K-' . $i),
            range(1, 1000)
        );
        $secondPage = [$this->customerPayload('c1001', 'K-1001')];

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => $fullPage])),
            new Response(200, [], json_encode(['result' => $secondPage])),
        ]);

        $result = $client->customers()->findByCompany('Acme');

        self::assertCount(1001, $result);
        self::assertSame('c1001', $result[1000]->id);
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    public function test_create_returns_new_customer_dto(): void
    {
        $client = $this->makeClient([
            new Response(201, [], json_encode($this->customerPayload('new-id', 'K-999'))),
        ]);

        $created = $client->customers()->create([
            'company'   => 'New GmbH',
            'partyType' => 'ORGANIZATION',
        ]);

        self::assertInstanceOf(CustomerDTO::class, $created);
        self::assertSame('new-id', $created->id);
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_returns_updated_customer_dto(): void
    {
        $updated          = $this->customerPayload();
        $updated['company'] = 'Updated GmbH';

        $client = $this->makeClient([
            new Response(200, [], json_encode($updated)),
        ]);

        $result = $client->customers()->update('cust-1', [
            'id'      => 'cust-1',
            'version' => '1',
            'company' => 'Updated GmbH',
        ]);

        self::assertSame('Updated GmbH', $result->company);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function test_delete_succeeds_on_204(): void
    {
        $client = $this->makeClient([
            new Response(204),
        ]);

        // Should not throw
        $client->customers()->delete('cust-1');
        $this->addToAssertionCount(1);
    }

    public function test_delete_throws_not_found_on_404(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([
            new Response(404, [], '{"message":"Not found"}'),
        ]);

        $client->customers()->delete('non-existent');
    }

    // -------------------------------------------------------------------------
    // findModifiedSince()
    // -------------------------------------------------------------------------

    public function test_find_modified_since_uses_last_modified_filter(): void
    {
        $container = [];
        $mock      = new MockHandler([
            new Response(200, [], json_encode([
                'result' => [$this->customerPayload()],
            ])),
        ]);
        $history   = \GuzzleHttp\Middleware::history($container);
        $stack     = HandlerStack::create($mock);
        $stack->push($history);
        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);
        $client = new WeclappClient($this->makeConfig(), guzzle: $guzzle);

        $client->customers()->findModifiedSince(1711400000000);

        $requestUri = (string) $container[0]['request']->getUri();
        self::assertStringContainsString('lastModifiedDate-gt=1711400000000', $requestUri);
    }

    // -------------------------------------------------------------------------
    // findByCustomerNumber()
    // -------------------------------------------------------------------------

    public function test_find_by_customer_number_returns_matching_customer(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                'result' => [$this->customerPayload('c1', 'K-100')],
            ])),
        ]);

        $customer = $client->customers()->findByCustomerNumber('K-100');

        self::assertSame('K-100', $customer->customerNumber);
    }

    public function test_find_by_customer_number_throws_not_found_for_empty_result(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $client->customers()->findByCustomerNumber('K-NONEXISTENT');
    }

    // -------------------------------------------------------------------------
    // cursor()
    // -------------------------------------------------------------------------

    public function test_cursor_yields_all_items(): void
    {
        $mock   = new MockHandler([
            new Response(200, [], json_encode(['result' => [$this->customerPayload('c1'), $this->customerPayload('c2', 'K-101')]])),
            // Second page returns fewer items than pageSize → no more pages
            new Response(200, [], json_encode(['result' => [$this->customerPayload('c3', 'K-102')]])),
        ]);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $client = new WeclappClient(
            new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0),
            guzzle: $guzzle,
        );

        $ids = [];
        foreach ($client->customers()->cursor(\miralsoft\weclapp\api\Query\QueryBuilder::new()->pageSize(2)) as $customer) {
            $ids[] = $customer->id;
        }

        self::assertSame(['c1', 'c2', 'c3'], $ids);
    }

    // -------------------------------------------------------------------------
    // PSR-16 cache
    // -------------------------------------------------------------------------

    public function test_list_all_uses_cache_on_hit(): void
    {
        $cachedData = [$this->customerPayload('cached-1')];
        $dtos       = array_map(
            fn (array $p) => CustomerDTO::fromArray($p),
            $cachedData
        );

        $cache = new class($dtos) implements \Psr\SimpleCache\CacheInterface {
            public function __construct(private readonly array $data) {}

            public function has(string $key): bool { return true; }
            public function get(string $key, mixed $default = null): mixed { return $this->data; }
            public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool { return true; }
            public function delete(string $key): bool { return true; }
            public function clear(): bool { return true; }
            public function getMultiple(iterable $keys, mixed $default = null): iterable { return []; }
            public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool { return true; }
            public function deleteMultiple(iterable $keys): bool { return true; }
        };

        // No HTTP responses queued — if it hits the network this test would fail
        $mock   = new MockHandler([]);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $client = new WeclappClient(
            new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0),
            cache: $cache,
            guzzle: $guzzle,
        );

        $result = $client->customers()->listAll();

        self::assertCount(1, $result);
        self::assertSame('cached-1', $result[0]->id);
    }

    public function test_list_all_stores_result_in_cache(): void
    {
        $stored = [];
        $cache  = new class($stored) implements \Psr\SimpleCache\CacheInterface {
            public array $stored = [];

            public function has(string $key): bool { return false; }
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool {
                $this->stored[$key] = $value;
                return true;
            }
            public function delete(string $key): bool { return true; }
            public function clear(): bool { return true; }
            public function getMultiple(iterable $keys, mixed $default = null): iterable { return []; }
            public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool { return true; }
            public function deleteMultiple(iterable $keys): bool { return true; }
        };

        $mock   = new MockHandler([
            new Response(200, [], json_encode(['result' => [$this->customerPayload()]])),
        ]);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $client = new WeclappClient(
            new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0),
            cache: $cache,
            guzzle: $guzzle,
        );

        $client->customers()->listAll();

        self::assertNotEmpty($cache->stored);
    }
}
