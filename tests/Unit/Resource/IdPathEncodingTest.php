<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that record IDs are URL-encoded before being placed in request paths.
 *
 * IDs may originate from external input (webhook payloads, user-supplied
 * references). Without encoding, an ID like "123/cancel" or "../party/id/5"
 * would redirect the request to a different endpoint path.
 */
final class IdPathEncodingTest extends TestCase
{
    private MockHandler $mock;

    private function makeClient(array $responses): WeclappClient
    {
        $this->mock = new MockHandler($responses);
        $guzzle     = new GuzzleClient(['handler' => HandlerStack::create($this->mock), 'http_errors' => false]);

        return new WeclappClient(
            new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0),
            guzzle: $guzzle,
        );
    }

    private function articlePayload(): array
    {
        return [
            'id'               => 'art-1',
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'articleNumber'    => 'ART-001',
            'name'             => 'Test Article',
        ];
    }

    public function test_find_encodes_slash_in_id(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode($this->articlePayload()))]);

        $client->articles()->find('123/cancel');

        $path = (string) $this->mock->getLastRequest()->getUri();
        self::assertStringContainsString('/article/id/123%2Fcancel', $path);
        self::assertStringNotContainsString('/article/id/123/cancel', $path);
    }

    public function test_find_encodes_query_injection_attempt(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode($this->articlePayload()))]);

        $client->articles()->find('5?dryRun=true');

        $uri = (string) $this->mock->getLastRequest()->getUri();
        self::assertStringContainsString('5%3FdryRun%3Dtrue', $uri);
        // The "?" must not start a real query string
        self::assertSame('', $this->mock->getLastRequest()->getUri()->getQuery());
    }

    public function test_find_encodes_path_traversal_attempt(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode($this->articlePayload()))]);

        $client->articles()->find('../party/id/5');

        $path = $this->mock->getLastRequest()->getUri()->getPath();
        self::assertStringContainsString('/article/id/..%2Fparty%2Fid%2F5', $path);
    }

    public function test_delete_encodes_id(): void
    {
        $client = $this->makeClient([new Response(204)]);

        $client->articles()->delete('123/../456');

        $path = $this->mock->getLastRequest()->getUri()->getPath();
        self::assertStringContainsString('/article/id/123%2F..%2F456', $path);
    }

    public function test_pdf_download_encodes_id_and_keeps_suffix(): void
    {
        $client = $this->makeClient([new Response(200, [], '%PDF-1.4')]);

        $client->salesInvoices()->getPdf('inv/99');

        $path = $this->mock->getLastRequest()->getUri()->getPath();
        self::assertStringContainsString('/salesInvoice/id/inv%2F99/downloadLatestSalesInvoicePdf', $path);
    }

    public function test_normal_numeric_id_is_unchanged(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode($this->articlePayload()))]);

        $client->articles()->find('998421');

        $path = $this->mock->getLastRequest()->getUri()->getPath();
        self::assertStringContainsString('/article/id/998421', $path);
    }
}
