<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\CustomerDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\FilterOperator;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Customer operations.
 *
 * Wraps the /api/v2/party endpoint (filtered to records with a customerNumber)
 * and provides all standard CRUD operations plus convenience methods for common
 * lookup patterns.
 *
 * Note: weclapp API v2 exposes all party types (customers, suppliers, contacts)
 * through the single /party endpoint. CustomerResource automatically restricts
 * all queries to parties that have a customerNumber set.
 *
 * Delta-sync example (ideal for DocBee or similar integrations):
 *
 * @example
 * $config = new WeclappConfig('miralsoft', 'your-token');
 * $client = new WeclappClient($config);
 *
 * // Fetch only customers changed since last sync
 * $changed = $client->customers()->findModifiedSince($lastSyncTimestampMs);
 * foreach ($changed as $customer) {
 *     $externalSystem->syncCustomer($customer);
 * }
 */
class CustomerResource extends AbstractResource
{
    protected string $endpoint = 'party';
    protected string $dtoClass = CustomerDTO::class;

    /**
     * Restrict all queries to parties that have a customerNumber (i.e. are actual customers).
     */
    protected function applyDefaultFilters(QueryBuilder $query): QueryBuilder
    {
        return $query->filter('customerNumber', FilterOperator::NOT_NULL);
    }

    /**
     * Find a customer by their customer number (e.g. "K-10042").
     *
     * @throws NotFoundException    If no customer with that number exists.
     * @throws WeclappApiException
     */
    public function findByCustomerNumber(string $customerNumber): CustomerDTO
    {
        $result = $this->list(
            QueryBuilder::new()
                ->filterEq('customerNumber', $customerNumber)
                ->pageSize(1)
        );

        if (empty($result->items)) {
            throw new NotFoundException(
                sprintf('Customer with number "%s" not found.', $customerNumber)
            );
        }

        /** @var CustomerDTO */
        return $result->items[0];
    }

    /**
     * Find customers by company name (exact match, case-insensitive).
     *
     * Returns all matching customers (there may be multiple with the same name).
     *
     * @return list<CustomerDTO>
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

        /** @var list<CustomerDTO> */
        return $result->items;
    }

    /**
     * Find customers by display name, regardless of whether they are an
     * ORGANIZATION (company name) or a PERSON (first/last name).
     *
     * Searches the company field first. If no results are found, a second
     * search is performed against the lastName field so that partial names
     * like "Smith" match both "John Smith" and "Smith Ltd.".
     *
     * @return list<CustomerDTO>
     *
     * @throws WeclappApiException
     */
    public function findByName(string $name): array
    {
        $byCompany = $this->list(
            QueryBuilder::new()
                ->filterIlike('company', $name)
                ->sort('company')
        );

        if (!empty($byCompany->items)) {
            /** @var list<CustomerDTO> */
            return $byCompany->items;
        }

        $byPerson = $this->list(
            QueryBuilder::new()
                ->filterIlike('lastName', $name)
                ->sort('lastName')
        );

        /** @var list<CustomerDTO> */
        return $byPerson->items;
    }

    /**
     * Find customers by e-mail address (exact match).
     *
     * @return list<CustomerDTO>
     *
     * @throws WeclappApiException
     */
    public function findByEmail(string $email): array
    {
        $result = $this->list(
            QueryBuilder::new()
                ->filterEq('email', $email)
        );

        /** @var list<CustomerDTO> */
        return $result->items;
    }

    /**
     * {@inheritdoc}
     *
     * @return CustomerDTO
     */
    public function find(string $id): CustomerDTO
    {
        /** @var CustomerDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return CustomerDTO
     */
    public function create(array $data): CustomerDTO
    {
        /** @var CustomerDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return CustomerDTO
     */
    public function update(string $id, array $data): CustomerDTO
    {
        /** @var CustomerDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<CustomerDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<CustomerDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
