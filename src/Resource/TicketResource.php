<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\TicketDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Ticket operations.
 *
 * Wraps the /api/v2/ticket endpoint. Tickets track customer support requests,
 * complaints or service work. They can be linked to parties, contacts,
 * contracts and sales orders.
 *
 * @see \miralsoft\weclapp\api\DTO\TicketDTO
 */
class TicketResource extends AbstractResource
{
    protected string $endpoint = 'ticket';
    protected string $dtoClass = TicketDTO::class;

    /**
     * Retrieve all tickets.
     *
     * @param QueryBuilder|null $query Optional filter / sort / pagination.
     * @return list<TicketDTO>
     *
     * @throws WeclappApiException
     */
    public function all(?QueryBuilder $query = null): array
    {
        /** @var list<TicketDTO> */
        return $this->listAll($query);
    }

    /**
     * {@inheritdoc}
     *
     * @return TicketDTO
     */
    public function find(string $id): TicketDTO
    {
        /** @var TicketDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return TicketDTO
     */
    public function create(array $data): TicketDTO
    {
        /** @var TicketDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return TicketDTO
     */
    public function update(string $id, array $data): TicketDTO
    {
        /** @var TicketDTO */
        return parent::update($id, $data);
    }

    /**
     * Find all tickets linked to a specific party (customer/contact/supplier).
     *
     * @param string $partyId The weclapp UUID of the party.
     * @return list<TicketDTO>
     *
     * @throws WeclappApiException
     */
    public function findByParty(string $partyId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('partyId', $partyId)
                ->sortByCreated('desc')
        );

        /** @var list<TicketDTO> */
        return $result;
    }

    /**
     * Find all tickets with a specific status.
     *
     * @param string $ticketStatusId The weclapp UUID of the ticket status.
     * @return list<TicketDTO>
     *
     * @throws WeclappApiException
     */
    public function findByStatus(string $ticketStatusId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('ticketStatusId', $ticketStatusId)
                ->sortByCreated('desc')
        );

        /** @var list<TicketDTO> */
        return $result;
    }

    /**
     * Find all tickets assigned to a specific user.
     *
     * @param string $userId The weclapp UUID of the assigned user.
     * @return list<TicketDTO>
     *
     * @throws WeclappApiException
     */
    public function findByAssignedUser(string $userId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('assignedUserId', $userId)
                ->sortByCreated('desc')
        );

        /** @var list<TicketDTO> */
        return $result;
    }

    /**
     * Find all tickets linked to a specific sales order.
     *
     * @param string $salesOrderId The weclapp UUID of the sales order.
     * @return list<TicketDTO>
     *
     * @throws WeclappApiException
     */
    public function findBySalesOrder(string $salesOrderId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('salesOrderId', $salesOrderId)
                ->sortByCreated('desc')
        );

        /** @var list<TicketDTO> */
        return $result;
    }
}
