<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\ContactDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\FilterOperator;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Contact operations.
 *
 * Wraps the /api/v2/party endpoint (filtered to contacts — parties linked
 * to a parent party via parentPartyId). Contacts are persons associated
 * with a customer or supplier organisation.
 *
 * For delta-sync with external systems use findModifiedSince()
 * to receive only contacts changed since the last synchronisation run.
 */
class ContactResource extends AbstractResource
{
    protected string $endpoint = 'party';
    protected string $dtoClass = ContactDTO::class;

    /**
     * Restrict all queries to parties that have a parentPartyId (i.e. are contacts
     * linked to a parent organisation, not standalone customer/supplier records).
     */
    protected function applyDefaultFilters(QueryBuilder $query): QueryBuilder
    {
        return $query->filter('parentPartyId', FilterOperator::NOT_NULL);
    }

    /**
     * Find contacts by e-mail address (exact match).
     *
     * @return list<ContactDTO>
     *
     * @throws WeclappApiException
     */
    public function findByEmail(string $email): array
    {
        $result = $this->list(
            QueryBuilder::new()->filterEq('email', $email)
        );

        /** @var list<ContactDTO> */
        return $result->items;
    }

    /**
     * Find all contacts linked to a given parent organisation party.
     *
     * weclapp stores the link via the `parentPartyId` field on the contact party.
     * The parent is typically a customer or supplier organisation party.
     *
     * Use this to load all contacts of a customer by passing the customer's
     * weclapp UUID (`CustomerDTO::$id`), not the customer number.
     *
     * @param string $parentPartyId The weclapp UUID of the parent organisation.
     * @return list<ContactDTO>
     *
     * @throws WeclappApiException
     */
    public function findByParentPartyId(string $parentPartyId): array
    {
        /** @var list<ContactDTO> */
        return $this->listAll(
            QueryBuilder::new()->filterEq('parentPartyId', $parentPartyId)
        );
    }

    /**
     * Find all contacts that belong to a specific customer.
     *
     * @param string $customerId The weclapp UUID of the customer.
     * @return list<ContactDTO>
     *
     * @throws WeclappApiException
     */
    public function findByCustomer(string $customerId): array
    {
        $result = $this->list(
            QueryBuilder::new()->filterEq('customerId', $customerId)
        );

        /** @var list<ContactDTO> */
        return $result->items;
    }

    /**
     * {@inheritdoc}
     *
     * @return ContactDTO
     */
    public function find(string $id): ContactDTO
    {
        /** @var ContactDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return ContactDTO
     */
    public function create(array $data): ContactDTO
    {
        /** @var ContactDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return ContactDTO
     */
    public function update(string $id, array $data): ContactDTO
    {
        /** @var ContactDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<ContactDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<ContactDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
