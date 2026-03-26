<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\ArticleCategoryDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Article Category operations.
 *
 * Wraps the /api/v2/articleCategory endpoint.
 */
class ArticleCategoryResource extends AbstractResource
{
    protected string $endpoint = 'articleCategory';
    protected string $dtoClass = ArticleCategoryDTO::class;

    /**
     * Find a category by its exact name.
     *
     * @throws NotFoundException    If no category with that name exists.
     * @throws WeclappApiException
     */
    public function findByName(string $name): ArticleCategoryDTO
    {
        $result = $this->list(
            QueryBuilder::new()
                ->filterEq('name', $name)
                ->pageSize(1)
        );

        if (empty($result->items)) {
            throw new NotFoundException(
                sprintf('Article category with name "%s" not found.', $name)
            );
        }

        /** @var ArticleCategoryDTO */
        return $result->items[0];
    }

    /**
     * Find all root categories (categories without a parent).
     *
     * @return list<ArticleCategoryDTO>
     *
     * @throws WeclappApiException
     */
    public function findRootCategories(): array
    {
        $result = $this->listAll(
            QueryBuilder::new()->filter('parentCategoryId', \miralsoft\weclapp\api\Query\FilterOperator::IS_NULL)
        );

        /** @var list<ArticleCategoryDTO> */
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return ArticleCategoryDTO
     */
    public function find(string $id): ArticleCategoryDTO
    {
        /** @var ArticleCategoryDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return ArticleCategoryDTO
     */
    public function create(array $data): ArticleCategoryDTO
    {
        /** @var ArticleCategoryDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return ArticleCategoryDTO
     */
    public function update(string $id, array $data): ArticleCategoryDTO
    {
        /** @var ArticleCategoryDTO */
        return parent::update($id, $data);
    }
}
