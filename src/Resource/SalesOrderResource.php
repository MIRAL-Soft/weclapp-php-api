<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Sales Order operations.
 *
 * Wraps the /api/v2/salesOrder endpoint.
 * Includes PDF download and delivery creation capabilities.
 */
class SalesOrderResource extends AbstractResource
{
    protected string $endpoint = 'salesOrder';
    protected string $dtoClass = SalesOrderDTO::class;

    /**
     * Download the order confirmation PDF for the given order.
     *
     * @param string $id The weclapp UUID of the sales order.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getPdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->endpoint . '/' . $id . '/downloadLatestOrderConfirmationPdf'
            )
        );
    }

    /**
     * Find all orders for a specific customer.
     *
     * @param string $customerId The weclapp UUID of the customer.
     * @return list<SalesOrderDTO>
     *
     * @throws WeclappApiException
     */
    public function findByCustomer(string $customerId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('customerId', $customerId)
                ->sortByCreated('desc')
        );

        /** @var list<SalesOrderDTO> */
        return $result;
    }

    /**
     * Find all orders with a specific status.
     *
     * Common status values: ORDER_ENTRY_IN_PROGRESS, ORDER_CONFIRMED,
     * DELIVERY_NOTE_CREATED, INVOICE_CREATED, ORDER_CANCELLED.
     *
     * @return list<SalesOrderDTO>
     *
     * @throws WeclappApiException
     */
    public function findByStatus(string $status): array
    {
        $result = $this->listAll(
            QueryBuilder::new()->filterEq('status', $status)
        );

        /** @var list<SalesOrderDTO> */
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesOrderDTO
     */
    public function find(string $id): SalesOrderDTO
    {
        /** @var SalesOrderDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesOrderDTO
     */
    public function create(array $data): SalesOrderDTO
    {
        /** @var SalesOrderDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesOrderDTO
     */
    public function update(string $id, array $data): SalesOrderDTO
    {
        /** @var SalesOrderDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<SalesOrderDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<SalesOrderDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
