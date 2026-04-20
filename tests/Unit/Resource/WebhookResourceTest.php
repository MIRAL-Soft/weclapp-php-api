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
}
