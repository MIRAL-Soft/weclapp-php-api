<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\AbstractDTO;
use miralsoft\weclapp\api\DTO\PaginatedResultDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Contract for all weclapp resource classes.
 *
 * Implement this interface to create custom resource classes that integrate
 * seamlessly with the rest of the client (e.g. for proxying, caching decorators,
 * or testing with fake implementations).
 *
 * @template T of AbstractDTO
 */
interface ResourceInterface
{
    /**
     * Returns the total number of records matching the optional filter query.
     *
     * @throws WeclappApiException
     */
    public function count(?QueryBuilder $query = null): int;

    /**
     * Retrieve a single record by its weclapp ID.
     *
     * @return T
     *
     * @throws NotFoundException   If the record does not exist.
     * @throws WeclappApiException
     */
    public function find(string $id): AbstractDTO;

    /**
     * Retrieve a paginated list of records.
     *
     * @return PaginatedResultDTO<T>
     *
     * @throws WeclappApiException
     */
    public function list(?QueryBuilder $query = null): PaginatedResultDTO;

    /**
     * Retrieve ALL records matching the query, paginating automatically.
     *
     * @return list<T>
     *
     * @throws WeclappApiException
     */
    public function listAll(?QueryBuilder $query = null): array;

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $data
     * @return T
     *
     * @throws WeclappApiException
     */
    public function create(array $data): AbstractDTO;

    /**
     * Update an existing record.
     *
     * @param array<string, mixed> $data
     * @return T
     *
     * @throws NotFoundException   If the record does not exist.
     * @throws WeclappApiException
     */
    public function update(string $id, array $data): AbstractDTO;

    /**
     * Delete a record by its weclapp ID.
     *
     * @throws NotFoundException   If the record does not exist.
     * @throws WeclappApiException
     */
    public function delete(string $id): void;
}
