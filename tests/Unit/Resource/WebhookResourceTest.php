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
            'id'              => $id,
            'createdDate'     => 1711400000000,
            'lastModifiedDate' => 1711400000000,
            'active'          => true,
            'eventType'       => 'party.updated',
            'callbackUrl'     => 'https://my-app.com/weclapp-events',
            'description'     => 'Sync to DocBee',
        ];
    }

    public function test_register_creates_webhook(): void
    {
        $client  = $this->makeClient([new Response(201, [], json_encode($this->webhookPayload()))]);
        $webhook = $client->webhooks()->register(
            eventType:   'party.updated',
            callbackUrl: 'https://my-app.com/weclapp-events',
            description: 'Sync to DocBee',
        );

        self::assertInstanceOf(WebhookDTO::class, $webhook);
        self::assertSame('party.updated', $webhook->eventType);
        self::assertSame('https://my-app.com/weclapp-events', $webhook->callbackUrl);
        self::assertTrue($webhook->active);
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
        self::assertSame('party.updated', $webhook->eventType);
        self::assertSame('Sync to DocBee', $webhook->description);
    }

    public function test_register_throws_on_http_callback_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/https/i');

        $client = $this->makeClient([]);
        $client->webhooks()->register(
            eventType:   'party.updated',
            callbackUrl: 'http://insecure.example.com/webhook',
        );
    }

    public function test_register_accepts_https_callback_url(): void
    {
        $client  = $this->makeClient([new Response(201, [], json_encode($this->webhookPayload()))]);
        $webhook = $client->webhooks()->register(
            eventType:   'party.updated',
            callbackUrl: 'https://secure.example.com/webhook',
        );

        self::assertInstanceOf(WebhookDTO::class, $webhook);
    }
}
