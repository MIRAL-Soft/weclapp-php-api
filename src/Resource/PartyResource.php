<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\PartyDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Party operations.
 *
 * The party endpoint is the common base entity for customers, suppliers and
 * contacts. Use this resource to resolve a partyId (e.g. from a salesInvoice)
 * to its identity data (customer number, name) without loading the full
 * customer or supplier payload.
 *
 * Endpoint: /api/v2/party
 *
 * @example Resolve a partyId from an invoice:
 * $party = $client->parties()->find($invoice->partyId);
 * echo $party->customerNumber; // e.g. "K-10042"
 */
class PartyResource extends AbstractResource
{
    protected string $endpoint = 'party';
    protected string $dtoClass = PartyDTO::class;

    /**
     * {@inheritdoc}
     *
     * @return PartyDTO
     */
    public function find(string $id): PartyDTO
    {
        /** @var PartyDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return PartyDTO
     */
    public function create(array $data): PartyDTO
    {
        /** @var PartyDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return PartyDTO
     */
    public function update(string $id, array $data): PartyDTO
    {
        /** @var PartyDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<PartyDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<PartyDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
