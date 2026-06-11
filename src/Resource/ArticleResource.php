<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\ArticleDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Article (product) operations.
 *
 * Wraps the /api/v2/article endpoint.
 *
 * @extends AbstractResource<\miralsoft\weclapp\api\DTO\ArticleDTO>
 */
class ArticleResource extends AbstractResource
{
    protected string $endpoint = 'article';
    protected string $dtoClass = ArticleDTO::class;

    /**
     * Find an article by its article number (SKU).
     *
     * @throws NotFoundException    If no article with that number exists.
     * @throws WeclappApiException
     */
    public function findByArticleNumber(string $articleNumber): ArticleDTO
    {
        $result = $this->list(
            QueryBuilder::new()
                ->filterEq('articleNumber', $articleNumber)
                ->pageSize(1)
        );

        if (empty($result->items)) {
            throw new NotFoundException(
                sprintf('Article with number "%s" not found.', $articleNumber)
            );
        }

        /** @var ArticleDTO */
        return $result->items[0];
    }

    /**
     * Find all articles in a given category.
     *
     * @param string $categoryId  The weclapp UUID of the category.
     * @return list<ArticleDTO>
     *
     * @throws WeclappApiException
     */
    public function findByCategory(string $categoryId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('articleCategoryId', $categoryId)
                ->sort('articleNumber')
        );

        /** @var list<ArticleDTO> */
        return $result;
    }

    /**
     * Return the article category ID for the article with the given article number.
     *
     * Thin convenience wrapper around findByArticleNumber() for callers that only
     * need the category without loading the full ArticleDTO themselves.
     *
     * @param string $articleNumber The article / SKU number (e.g. "ART-10042").
     * @return string|null The weclapp UUID of the category, or null if no category is assigned.
     *
     * @throws NotFoundException   If no article with that number exists.
     * @throws WeclappApiException
     */
    public function findCategoryIdByNumber(string $articleNumber): ?string
    {
        return $this->findByArticleNumber($articleNumber)->articleCategoryId;
    }

    /**
     * Find all currently active and in-stock articles.
     *
     * @return list<ArticleDTO>
     *
     * @throws WeclappApiException
     */
    public function findInStock(): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('active', true)
                ->filterGt('availableStock', 0)
        );

        /** @var list<ArticleDTO> */
        return $result;
    }

    /**
     * Fetch the complete raw API response for an article as a plain array.
     *
     * Returns every field the weclapp API provides — including read-only system
     * fields not covered by ArticleDTO. The result can be stored as a backup
     * and passed unmodified to update() to restore the article to this exact state.
     *
     * See AbstractResource::findRaw() for the full contract including the
     * optimistic-locking caveat when using the backup for a restore.
     *
     * @param string $id The weclapp UUID of the article.
     * @return array<string, mixed>
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException If the article does not exist.
     * @throws \miralsoft\weclapp\api\Exception\WeclappApiException
     */
    public function findRaw(string $id): array
    {
        return parent::findRaw($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return ArticleDTO
     */
    public function patch(string $id, array $fields): ArticleDTO
    {
        /** @var ArticleDTO */
        return parent::patch($id, $fields);
    }

    /**
     * {@inheritdoc}
     *
     * @return ArticleDTO
     */
    public function find(string $id): ArticleDTO
    {
        /** @var ArticleDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return ArticleDTO
     */
    public function create(array $data): ArticleDTO
    {
        /** @var ArticleDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return ArticleDTO
     */
    public function update(string $id, array $data): ArticleDTO
    {
        /** @var ArticleDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<ArticleDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<ArticleDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
