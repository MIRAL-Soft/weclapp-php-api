<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\WebhookDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WebhookResource.
 */
class WebhookResourceTest extends TestCase
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

    private function webhookPayload(string $id = 'wh-1'): array
    {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711400000000,
            'atCreate'         => true,
            'atDelete'         => false,
            'atUpdate'         => true,
            'deactivatedDate'  => null,
            'entityName'       => 'party',
            'errorMessage'     => null,
            'requestMethod'    => 'POST',
            'url'              => 'https://my-app.com/weclapp-events',
        ];
    }

    public function test_register_creates_webhook(): void
    {
        $client  = $this->makeClient([new Response(201, [], json_encode($this->webhookPayload()))]);
        $webhook = $client->webhooks()->register(
            entityName: 'party',
            url:        'https://my-app.com/weclapp-events',
            atCreate:   true,
            atUpdate:   true,
        );

        self::assertInstanceOf(WebhookDTO::class, $webhook);
        self::assertSame('party', $webhook->entityName);
        self::assertSame('https://my-app.com/weclapp-events', $webhook->url);
        self::assertTrue($webhook->atCreate);
        self::assertTrue($webhook->atUpdate);
        self::assertFalse($webhook->atDelete);
    }

    public function test_all_returns_list_of_webhooks(): void
    {
        $body   = json_encode(['result' => [$this->webhookPayload('wh-1'), $this->webhookPayload('wh-2')]]);
        $client = $this->makeClient([new Response(200, [], $body)]);

        $webhooks = $client->webhooks()->all();

        self::assertCount(2, $webhooks);
        self::assertContainsOnlyInstancesOf(WebhookDTO::class, $webhooks);
    }

    public function test_delete_webhook(): void
    {
        $client = $this->makeClient([new Response(204)]);

        $client->webhooks()->delete('wh-1');
        $this->addToAssertionCount(1);
    }

    public function test_webhook_dto_from_array(): void
    {
        $webhook = WebhookDTO::fromArray($this->webhookPayload());

        self::assertSame('wh-1', $webhook->id);
        self::assertSame('party', $webhook->entityName);
        self::assertSame('POST', $webhook->requestMethod);
        self::assertSame('https://my-app.com/weclapp-events', $webhook->url);
        self::assertTrue($webhook->atCreate);
        self::assertFalse($webhook->atDelete);
        self::assertTrue($webhook->atUpdate);
        self::assertNull($webhook->deactivatedDate);
        self::assertNull($webhook->errorMessage);
    }

    public function test_webhook_is_active_when_deactivated_date_is_null(): void
    {
        $webhook = WebhookDTO::fromArray($this->webhookPayload());

        self::assertTrue($webhook->isActive());
    }

    public function test_webhook_is_inactive_when_deactivated_date_is_set(): void
    {
        $data                    = $this->webhookPayload();
        $data['deactivatedDate'] = 1711500000000;

        $webhook = WebhookDTO::fromArray($data);

        self::assertFalse($webhook->isActive());
    }

    public function test_register_throws_when_no_trigger_is_enabled(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $client = $this->makeClient([]);
        $client->webhooks()->register(
            entityName: 'party',
            url:        'https://my-app.com/weclapp-events',
        );
    }

    public function test_register_throws_on_invalid_request_method(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $client = $this->makeClient([]);
        $client->webhooks()->register(
            entityName:    'party',
            url:           'https://my-app.com/weclapp-events',
            atCreate:      true,
            requestMethod: 'PUT',
        );
    }

    public function test_register_accepts_get_request_method(): void
    {
        $payload              = $this->webhookPayload();
        $payload['requestMethod'] = 'GET';

        $client  = $this->makeClient([new Response(201, [], json_encode($payload))]);
        $webhook = $client->webhooks()->register(
            entityName:    'party',
            url:           'https://my-app.com/weclapp-events',
            atCreate:      true,
            requestMethod: 'GET',
        );

        self::assertSame('GET', $webhook->requestMethod);
    }

    // -------------------------------------------------------------------------
    // ensureSubscription
    // -------------------------------------------------------------------------

    public function test_ensure_subscription_creates_new_webhook_when_none_exists(): void
    {
        // listAll() → empty list; create() → new webhook
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
            new Response(201, [], json_encode($this->webhookPayload('wh-new'))),
        ]);

        $webhook = $client->webhooks()->ensureSubscription(
            entityName: 'salesOrder',
            url:        'https://my-app.com/weclapp-events',
            atCreate:   true,
            atUpdate:   true,
        );

        self::assertInstanceOf(WebhookDTO::class, $webhook);
        self::assertSame('wh-new', $webhook->id);
    }

    public function test_ensure_subscription_returns_existing_when_flags_already_match(): void
    {
        // listAll() returns a webhook that already satisfies the request
        $existing = $this->webhookPayload('wh-1');   // atCreate=true, atUpdate=true, atDelete=false
        $client   = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$existing]])),
            // no POST or PUT expected
        ]);

        $webhook = $client->webhooks()->ensureSubscription(
            entityName: 'party',
            url:        'https://my-app.com/weclapp-events',
            atCreate:   true,
            atUpdate:   true,
        );

        self::assertSame('wh-1', $webhook->id);
    }

    public function test_ensure_subscription_updates_with_merged_flags_when_new_flag_requested(): void
    {
        // Existing: atCreate=true, atUpdate=true, atDelete=false
        // Requested: atDelete=true (not yet set)
        // Expected PUT: atCreate=true, atUpdate=true, atDelete=true
        $existing = $this->webhookPayload('wh-1');  // atDelete is false

        $updated               = $this->webhookPayload('wh-1');
        $updated['atDelete']   = true;
        $updated['version']    = '2';

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$existing]])),  // listAll
            new Response(200, [], json_encode($updated)),                    // PUT
        ]);

        $result = $client->webhooks()->ensureSubscription(
            entityName: 'party',
            url:        'https://my-app.com/weclapp-events',
            atCreate:   false,  // already true on existing → stays true after merge
            atUpdate:   false,  // already true on existing → stays true after merge
            atDelete:   true,   // not yet set → triggers update
        );

        self::assertTrue($result->atDelete);
        self::assertTrue($result->atCreate);  // preserved from existing
        self::assertTrue($result->atUpdate);  // preserved from existing
    }

    public function test_ensure_subscription_updates_when_request_method_differs(): void
    {
        $existing = $this->webhookPayload('wh-1');  // requestMethod=POST

        $updated                  = $this->webhookPayload('wh-1');
        $updated['requestMethod'] = 'GET';

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$existing]])),
            new Response(200, [], json_encode($updated)),
        ]);

        $result = $client->webhooks()->ensureSubscription(
            entityName:    'party',
            url:           'https://my-app.com/weclapp-events',
            atCreate:      true,
            atUpdate:      true,
            requestMethod: 'GET',
        );

        self::assertSame('GET', $result->requestMethod);
    }

    public function test_ensure_subscription_throws_for_empty_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        $client = $this->makeClient([]);
        $client->webhooks()->ensureSubscription(entityName: 'party', url: '', atCreate: true);
    }

    public function test_ensure_subscription_throws_when_no_event_flag_is_set(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of');

        $client = $this->makeClient([]);
        $client->webhooks()->ensureSubscription(
            entityName: 'party',
            url:        'https://my-app.com/weclapp-events',
        );
    }

    public function test_ensure_subscription_throws_for_invalid_request_method(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $client = $this->makeClient([]);
        $client->webhooks()->ensureSubscription(
            entityName:    'party',
            url:           'https://my-app.com/weclapp-events',
            atCreate:      true,
            requestMethod: 'DELETE',
        );
    }

    // -------------------------------------------------------------------------
    // findByUrl
    // -------------------------------------------------------------------------

    public function test_find_by_url_returns_matching_webhooks(): void
    {
        $match   = $this->webhookPayload('wh-1');
        $noMatch = array_merge($this->webhookPayload('wh-2'), ['url' => 'https://other.example.com/hook']);

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$match, $noMatch]])),
        ]);

        $results = $client->webhooks()->findByUrl('https://my-app.com/weclapp-events');

        self::assertCount(1, $results);
        self::assertSame('wh-1', $results[0]->id);
    }

    public function test_find_by_url_returns_empty_array_when_no_match(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->webhookPayload()]])),
        ]);

        $results = $client->webhooks()->findByUrl('https://other.example.com/hook');

        self::assertSame([], $results);
    }

    public function test_find_by_url_does_not_match_url_with_trailing_slash(): void
    {
        // Stored URL has no trailing slash; query has trailing slash → no match (exact comparison)
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->webhookPayload()]])),
        ]);

        $results = $client->webhooks()->findByUrl('https://my-app.com/weclapp-events/');

        self::assertSame([], $results);
    }

    // -------------------------------------------------------------------------
    // findByEntityName
    // -------------------------------------------------------------------------

    public function test_find_by_entity_name_returns_matching_webhooks(): void
    {
        $party  = $this->webhookPayload('wh-1');  // entityName = 'party'
        $order  = array_merge($this->webhookPayload('wh-2'), ['entityName' => 'salesOrder']);

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$party, $order]])),
        ]);

        $results = $client->webhooks()->findByEntityName('salesOrder');

        self::assertCount(1, $results);
        self::assertSame('wh-2', $results[0]->id);
    }

    public function test_find_by_entity_name_returns_empty_array_when_no_match(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->webhookPayload()]])),
        ]);

        $results = $client->webhooks()->findByEntityName('article');

        self::assertSame([], $results);
    }

    // -------------------------------------------------------------------------
    // deactivate
    // -------------------------------------------------------------------------

    public function test_deactivate_sets_deactivated_date(): void
    {
        $active     = $this->webhookPayload('wh-1');   // deactivatedDate = null

        $deactivated                     = $this->webhookPayload('wh-1');
        $deactivated['deactivatedDate']  = 1711500000000;

        $client = $this->makeClient([
            new Response(200, [], json_encode($active)),       // find()
            new Response(200, [], json_encode($deactivated)), // update()
        ]);

        $result = $client->webhooks()->deactivate('wh-1');

        self::assertFalse($result->isActive());
        self::assertNotNull($result->deactivatedDate);
    }

    public function test_deactivate_is_idempotent_when_already_inactive(): void
    {
        $inactive                    = $this->webhookPayload('wh-1');
        $inactive['deactivatedDate'] = 1711500000000;

        $client = $this->makeClient([
            new Response(200, [], json_encode($inactive)),  // find() only — no PUT expected
        ]);

        $result = $client->webhooks()->deactivate('wh-1');

        self::assertFalse($result->isActive());
    }

    // -------------------------------------------------------------------------
    // reactivate
    // -------------------------------------------------------------------------

    public function test_reactivate_clears_deactivated_date(): void
    {
        $inactive                    = $this->webhookPayload('wh-1');
        $inactive['deactivatedDate'] = 1711500000000;

        $active = $this->webhookPayload('wh-1');  // deactivatedDate = null

        $client = $this->makeClient([
            new Response(200, [], json_encode($inactive)), // find()
            new Response(200, [], json_encode($active)),   // update()
        ]);

        $result = $client->webhooks()->reactivate('wh-1');

        self::assertTrue($result->isActive());
        self::assertNull($result->deactivatedDate);
    }

    public function test_reactivate_is_idempotent_when_already_active(): void
    {
        $active = $this->webhookPayload('wh-1');  // deactivatedDate = null

        $client = $this->makeClient([
            new Response(200, [], json_encode($active)),  // find() only — no PUT expected
        ]);

        $result = $client->webhooks()->reactivate('wh-1');

        self::assertTrue($result->isActive());
    }
}
