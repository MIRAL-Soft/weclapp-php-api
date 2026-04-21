<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\ArticleCategoryDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for ArticleCategoryResource (/api/v2/articleCategory).
 * All tests are read-only.
 */
class ArticleCategoryResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_category_dtos(): void
    {
        $result = $this->client()->articleCategories()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertContainsOnlyInstancesOf(ArticleCategoryDTO::class, $result->items);
    }

    public function test_first_category_has_required_fields(): void
    {
        $result = $this->client()->articleCategories()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No article categories found in this tenant.');
        }

        $category = $result->items[0];

        self::assertNotEmpty($category->id);
        self::assertIsInt($category->createdDate);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->articleCategories()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No article categories found in this tenant.');
        }

        $id       = $result->items[0]->id;
        $category = $this->client()->articleCategories()->find($id);

        self::assertInstanceOf(ArticleCategoryDTO::class, $category);
        self::assertSame($id, $category->id);
    }

    public function test_find_root_categories_returns_only_root_items(): void
    {
        $roots = $this->client()->articleCategories()->findRootCategories();

        self::assertContainsOnlyInstancesOf(ArticleCategoryDTO::class, $roots);

        foreach ($roots as $root) {
            self::assertNull(
                $root->parentCategoryId,
                "findRootCategories() returned a non-root category (id={$root->id}).",
            );
        }
    }

    public function test_find_by_name_throws_for_unknown_name(): void
    {
        $this->expectException(NotFoundException::class);

        $this->client()->articleCategories()->findByName('__THIS_CATEGORY_DOES_NOT_EXIST__');
    }

    public function test_count_returns_non_negative_integer(): void
    {
        $count = $this->client()->articleCategories()->count();

        self::assertIsInt($count);
        self::assertGreaterThanOrEqual(0, $count);
    }
}
