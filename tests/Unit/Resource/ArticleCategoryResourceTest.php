<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\ArticleCategoryDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArticleCategoryResource.
 */
class ArticleCategoryResourceTest extends TestCase
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

    private function categoryPayload(
        string  $id = 'cat-1',
        string  $name = 'Electronics',
        ?string $parentId = null,
    ): array {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'name'             => $name,
            'parentCategoryId' => $parentId,
        ];
    }

    public function test_find_returns_category_dto(): void
    {
        $client   = $this->makeClient([new Response(200, [], json_encode($this->categoryPayload()))]);
        $category = $client->articleCategories()->find('cat-1');

        self::assertInstanceOf(ArticleCategoryDTO::class, $category);
        self::assertSame('cat-1', $category->id);
        self::assertSame('Electronics', $category->name);
        self::assertNull($category->parentCategoryId);
    }

    public function test_find_by_name_returns_matching_category(): void
    {
        $client   = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$this->categoryPayload()]])),
        ]);
        $category = $client->articleCategories()->findByName('Electronics');

        self::assertSame('Electronics', $category->name);
    }

    public function test_find_by_name_throws_when_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $client->articleCategories()->findByName('NonExistent');
    }

    public function test_find_root_categories_returns_list(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [
                $this->categoryPayload('cat-1', 'Electronics'),
                $this->categoryPayload('cat-2', 'Software'),
            ]])),
        ]);

        $roots = $client->articleCategories()->findRootCategories();

        self::assertCount(2, $roots);
        self::assertContainsOnlyInstancesOf(ArticleCategoryDTO::class, $roots);
    }

    public function test_is_root_category_returns_true_when_no_parent(): void
    {
        $category = ArticleCategoryDTO::fromArray($this->categoryPayload());

        self::assertTrue($category->isRootCategory());
    }

    public function test_is_root_category_returns_false_when_has_parent(): void
    {
        $category = ArticleCategoryDTO::fromArray(
            $this->categoryPayload('cat-child', 'Smartphones', 'cat-1')
        );

        self::assertFalse($category->isRootCategory());
    }

    public function test_create_category(): void
    {
        $client   = $this->makeClient([
            new Response(201, [], json_encode($this->categoryPayload('new-cat', 'New Category'))),
        ]);
        $category = $client->articleCategories()->create(['name' => 'New Category']);

        self::assertSame('new-cat', $category->id);
        self::assertSame('New Category', $category->name);
    }

    public function test_delete_category(): void
    {
        $client = $this->makeClient([new Response(204)]);

        $client->articleCategories()->delete('cat-1');
        $this->addToAssertionCount(1);
    }
}
