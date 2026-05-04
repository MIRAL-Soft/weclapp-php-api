<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\ArticleDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for ArticleResource.
 *
 * All tests are read-only — no data is created or modified.
 */
class ArticleResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_paginated_result(): void
    {
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertGreaterThanOrEqual(0, $result->total);
        self::assertContainsOnlyInstancesOf(ArticleDTO::class, $result->items);
    }

    public function test_first_article_has_required_fields(): void
    {
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant.');
        }

        $article = $result->items[0];

        self::assertNotEmpty($article->id);
        self::assertNotEmpty($article->articleNumber);
        self::assertIsInt($article->createdDate);
    }

    public function test_find_by_article_number_returns_same_record(): void
    {
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant.');
        }

        $articleNumber = $result->items[0]->articleNumber;
        $found         = $this->client()->articles()->findByArticleNumber($articleNumber);

        self::assertInstanceOf(ArticleDTO::class, $found);
        self::assertSame($articleNumber, $found->articleNumber);
    }

    public function test_find_by_article_number_throws_for_unknown(): void
    {
        $this->expectException(NotFoundException::class);

        $this->client()->articles()->findByArticleNumber('__THIS_ARTICLE_DOES_NOT_EXIST__');
    }

    public function test_count_returns_positive_integer(): void
    {
        // count() calls /article/count and returns the real total.
        // list().total is NOT the global count — weclapp list responses do not
        // include a recordCount field, so total falls back to items-on-page.
        $count = $this->client()->articles()->count();

        self::assertIsInt($count);
        self::assertGreaterThan(0, $count, 'Expected at least one article in this tenant.');
    }

    public function test_find_category_id_by_number_returns_nullable_string(): void
    {
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(5),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant.');
        }

        // Find an article that has an articleNumber
        $articleWithNumber = null;
        foreach ($result->items as $article) {
            if (!empty($article->articleNumber)) {
                $articleWithNumber = $article;
                break;
            }
        }

        if ($articleWithNumber === null) {
            $this->markTestSkipped('No article with articleNumber found.');
        }

        $categoryId = $this->client()->articles()->findCategoryIdByNumber($articleWithNumber->articleNumber);

        // Returns string ID or null — both are valid (article may not be categorised)
        self::assertTrue(
            $categoryId === null || is_string($categoryId),
            'findCategoryIdByNumber() must return string|null.',
        );
    }
}
