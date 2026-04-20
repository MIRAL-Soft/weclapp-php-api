<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\ArticleDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArticleResource.
 */
class ArticleResourceTest extends TestCase
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

    private function articlePayload(string $id = 'art-1', string $number = 'ART-001'): array
    {
        return [
            'id'                   => $id,
            'version'              => '1',
            'createdDate'          => 1711400000000,
            'lastModifiedDate'     => 1711450000000,
            'articleNumber'        => $number,
            'name'                 => 'Test Article',
            'active'               => true,
            'availableInSale'      => true,   // correct API key (formerly: sellable)
            'serialNumberRequired' => false,
            'batchNumberRequired'  => false,
            'tags'                 => [],
            'customAttributes'     => [],
            'articleImages'        => [],
        ];
    }

    public function test_find_returns_article_dto(): void
    {
        $client  = $this->makeClient([new Response(200, [], json_encode($this->articlePayload()))]);
        $article = $client->articles()->find('art-1');

        self::assertInstanceOf(ArticleDTO::class, $article);
        self::assertSame('ART-001', $article->articleNumber);
        self::assertTrue($article->active);
        self::assertTrue($article->availableInSale);
    }

    public function test_find_by_article_number_returns_correct_article(): void
    {
        $client  = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->articlePayload('art-1', 'ART-001')]])),
        ]);
        $article = $client->articles()->findByArticleNumber('ART-001');

        self::assertSame('ART-001', $article->articleNumber);
    }

    public function test_find_by_article_number_throws_when_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $client->articles()->findByArticleNumber('UNKNOWN');
    }

    public function test_is_bill_of_material_returns_false_when_no_items(): void
    {
        // availableStock / isInStock() are not part of the article schema;
        // stock is resolved from a separate endpoint.
        $article = ArticleDTO::fromArray($this->articlePayload());

        self::assertFalse($article->isBillOfMaterial());
    }

    public function test_get_main_image_returns_null_when_no_images(): void
    {
        $article = ArticleDTO::fromArray($this->articlePayload());

        self::assertNull($article->getMainImage());
    }

    public function test_create_article(): void
    {
        $client  = $this->makeClient([
            new Response(201, [], json_encode($this->articlePayload('new-id', 'ART-NEW'))),
        ]);
        $article = $client->articles()->create([
            'articleNumber' => 'ART-NEW',
            'name'          => 'New Article',
        ]);

        self::assertSame('new-id', $article->id);
        self::assertSame('ART-NEW', $article->articleNumber);
    }

    public function test_delete_article(): void
    {
        $client = $this->makeClient([new Response(204)]);

        $client->articles()->delete('art-1');
        $this->addToAssertionCount(1);
    }
}
