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
 *
 * @extends AbstractResource<\miralsoft\weclapp\api\DTO\ContactDTO>
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
        /** @var list<ContactDTO> */
        return $this->listAll(
            QueryBuilder::new()->filterEq('email', $email)
        );
    }

    /**
     * Loads full ContactDTO objects from weclapp customer contact stubs.
     *
     * When fetching a customer from the weclapp API, the `contacts` field contains
     * only stub objects with a single `id` key — no other fields are populated.
     * This method resolves each stub to a full ContactDTO by calling find() individually.
     *
     * Note: Filtering contacts via `parentPartyId-eq` does NOT work because weclapp
     * returns contact objects with `parentPartyId: null`, even when the contact is
     * linked to a parent organisation. Loading by ID is the only reliable approach.
     *
     * @param list<array> $stubs  The raw stubs from CustomerDTO::$contacts.
     * @return list<ContactDTO>
     */
    public function loadFromStubs(array $stubs): array
    {
        $result = [];
        foreach ($stubs as $stub) {
            if (!is_array($stub) || !isset($stub['id'])) {
                continue;
            }
            try {
                $result[] = $this->find($stub['id']);
            } catch (WeclappApiException) {
                // Stub refers to a contact that no longer exists — skip silently.
            }
        }
        return $result;
    }

    /**
     * @deprecated Does not work reliably. The weclapp API returns contact objects
     *             with `parentPartyId: null` even for linked contacts, so this filter
     *             always returns zero results. Use {@see loadFromStubs()} instead,
     *             passing CustomerDTO::$contacts.
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
     * @deprecated This method is misleading and will be removed in a future release.
     *
     *   The filter field `customerId` on the weclapp `/api/v2/party` endpoint is
     *   **not** the party UUID of the customer — it is an internal customer-assignment
     *   field that is rarely populated on contact records. Passing `CustomerDTO::$id`
     *   (the party UUID) here will silently return an empty list in most tenants.
     *
     *   **Use {@see loadFromStubs()} instead:**
     *   ```php
     *   // Before (broken for most callers):
     *   $contacts = $client->contacts()->findByCustomer($customer->id);
     *
     *   // After (correct):
     *   $contacts = $client->contacts()->loadFromStubs($customer->contacts);
     *   ```
     *   `CustomerDTO::$contacts` contains the raw stubs `[["id" => "..."], ...]`
     *   that weclapp embeds in the customer response. `loadFromStubs()` resolves
     *   each stub to a full `ContactDTO` via individual `find()` calls.
     *
     * @param string $customerId Internal customer-assignment ID (NOT the party UUID).
     * @return list<ContactDTO>
     *
     * @throws WeclappApiException
     */
    public function findByCustomer(string $customerId): array
    {
        /** @var list<ContactDTO> */
        return $this->listAll(
            QueryBuilder::new()->filterEq('customerId', $customerId)
        );
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
