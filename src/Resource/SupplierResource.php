<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\SupplierDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\FilterOperator;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Supplier operations.
 *
 * Wraps the /api/v2/party endpoint (filtered to records with a supplierNumber).
 */
class SupplierResource extends AbstractResource
{
    protected string $endpoint = 'party';
    protected string $dtoClass = SupplierDTO::class;

    /**
     * Restrict all queries to parties that have a supplierNumber (i.e. are actual suppliers).
     */
    protected function applyDefaultFilters(QueryBuilder $query): QueryBuilder
    {
        return $query->filter('supplierNumber', FilterOperator::NOT_NULL);
    }

    /**
     * Find suppliers by company name (case-insensitive contains search).
     *
     * @return list<SupplierDTO>
     *
     * @throws WeclappApiException
     */
    public function findByCompany(string $company): array
    {
        $result = $this->list(
            QueryBuilder::new()
                ->filterIlike('company', $company)
                ->sort('company')
        );

        /** @var list<SupplierDTO> */
        return $result->items;
    }

    /**
     * {@inheritdoc}
     *
     * @return SupplierDTO
     */
    public function find(string $id): SupplierDTO
    {
        /** @var SupplierDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return SupplierDTO
     */
    public function create(array $data): SupplierDTO
    {
        /** @var SupplierDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return SupplierDTO
     */
    public function update(string $id, array $data): SupplierDTO
    {
        /** @var SupplierDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<SupplierDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<SupplierDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
