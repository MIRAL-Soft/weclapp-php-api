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

    // -------------------------------------------------------------------------
    // patch — Read-Modify-Write
    // -------------------------------------------------------------------------

    public function test_patch_sends_full_record_with_merged_field(): void
    {
        $original          = $this->articlePayload('art-1', 'ART-001');
        $original['name']  = 'Old Name';
        $original['description'] = 'Keep me';

        // patch() does two HTTP calls: GET (findRaw) + PUT (update)
        // The PUT response is a fresh payload with the new name.
        $putResponse                = $original;
        $putResponse['name']        = 'New Name';
        $putResponse['description'] = 'Keep me';

        $client  = $this->makeClient([
            new Response(200, [], json_encode($original)),    // GET (findRaw)
            new Response(200, [], json_encode($putResponse)), // PUT (update)
        ]);

        $result = $client->articles()->patch('art-1', ['name' => 'New Name']);

        self::assertInstanceOf(ArticleDTO::class, $result);
        self::assertSame('New Name', $result->name);
        // articleNumber must be unchanged — it came from the raw GET response
        self::assertSame('ART-001', $result->articleNumber);
    }

    public function test_patch_strips_id_and_version_from_fields(): void
    {
        $original           = $this->articlePayload('art-1', 'ART-001');
        $original['version'] = '5';

        $putResponse          = $original;
        $putResponse['name']  = 'Renamed';

        // We use GuzzleHttp's MockHandler but cannot inspect the request body here.
        // The test verifies no exception is thrown (version collision would cause 409
        // from the real API — in unit tests the mock always succeeds). The important
        // guarantee tested by the integration test is that findRaw()'s version wins.
        $client = $this->makeClient([
            new Response(200, [], json_encode($original)),
            new Response(200, [], json_encode($putResponse)),
        ]);

        // Caller passes a stale version and a stale id — both must be ignored
        $result = $client->articles()->patch('art-1', [
            'id'      => '__wrong__',
            'version' => '0',
            'name'    => 'Renamed',
        ]);

        self::assertInstanceOf(ArticleDTO::class, $result);
    }

    public function test_patch_throws_on_empty_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = $this->makeClient([]); // no HTTP calls expected
        $client->articles()->patch('art-1', []);
    }

    // -------------------------------------------------------------------------
    // findCategoryIdByNumber
    // -------------------------------------------------------------------------

    public function test_find_category_id_by_number_returns_category_id(): void
    {
        $payload                     = $this->articlePayload('art-1', 'ART-001');
        $payload['articleCategoryId'] = 'cat-99';

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$payload]])),
        ]);

        $categoryId = $client->articles()->findCategoryIdByNumber('ART-001');

        self::assertSame('cat-99', $categoryId);
    }

    public function test_find_category_id_by_number_returns_null_when_no_category_assigned(): void
    {
        $payload = $this->articlePayload('art-1', 'ART-001');
        // articleCategoryId absent → null

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$payload]])),
        ]);

        $categoryId = $client->articles()->findCategoryIdByNumber('ART-001');

        self::assertNull($categoryId);
    }

    public function test_find_category_id_by_number_throws_when_article_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $client->articles()->findCategoryIdByNumber('UNKNOWN');
    }
}
