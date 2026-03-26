<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\ContactDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Contact operations.
 *
 * Wraps the /api/v2/contact endpoint. Contacts are persons linked
 * to a customer organisation.
 *
 * For delta-sync with external systems use findModifiedSince()
 * to receive only contacts changed since the last synchronisation run.
 */
class ContactResource extends AbstractResource
{
    protected string $endpoint = 'contact';
    protected string $dtoClass = ContactDTO::class;

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
