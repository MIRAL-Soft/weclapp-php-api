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

    public function test_count_matches_list_total(): void
    {
        $query  = QueryBuilder::new()->pageSize(1);
        $result = $this->client()->articles()->list($query);
        $count  = $this->client()->articles()->count($query);

        self::assertSame($result->total, $count);
    }
}
