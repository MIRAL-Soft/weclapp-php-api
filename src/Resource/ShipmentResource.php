<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\ShipmentDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Shipment operations.
 *
 * Wraps the /api/v2/shipment endpoint. Shipments represent outgoing deliveries
 * created from Sales Orders. They contain parcel records with tracking information
 * and line items linking shipped quantities to source order items.
 *
 * PDF downloads (delivery note, picking list, shipping labels) are available
 * via the download methods on this resource.
 *
 * @see \miralsoft\weclapp\api\DTO\ShipmentDTO
 */
class ShipmentResource extends AbstractResource
{
    protected string $endpoint = 'shipment';
    protected string $dtoClass = ShipmentDTO::class;

    /**
     * Retrieve all shipments.
     *
     * @param QueryBuilder|null $query Optional filter / sort / pagination.
     * @return list<ShipmentDTO>
     *
     * @throws WeclappApiException
     */
    public function all(?QueryBuilder $query = null): array
    {
        /** @var list<ShipmentDTO> */
        return $this->listAll($query);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShipmentDTO
     */
    public function find(string $id): ShipmentDTO
    {
        /** @var ShipmentDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShipmentDTO
     */
    public function create(array $data): ShipmentDTO
    {
        /** @var ShipmentDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShipmentDTO
     */
    public function update(string $id, array $data): ShipmentDTO
    {
        /** @var ShipmentDTO */
        return parent::update($id, $data);
    }

    /**
     * Find all shipments linked to a specific sales order.
     *
     * @param string $salesOrderId The weclapp UUID of the sales order.
     * @return list<ShipmentDTO>
     *
     * @throws WeclappApiException
     */
    public function findBySalesOrder(string $salesOrderId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('salesOrders.id', $salesOrderId)
                ->sortByCreated('desc')
        );

        /** @var list<ShipmentDTO> */
        return $result;
    }

    /**
     * Find all shipments for a specific recipient party.
     *
     * @param string $partyId The weclapp UUID of the recipient party.
     * @return list<ShipmentDTO>
     *
     * @throws WeclappApiException
     */
    public function findByParty(string $partyId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('recipientPartyId', $partyId)
                ->sortByCreated('desc')
        );

        /** @var list<ShipmentDTO> */
        return $result;
    }

    /**
     * Download the delivery note PDF for the given shipment.
     *
     * @param string $id The weclapp UUID of the shipment.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getDeliveryNotePdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->endpoint . '/id/' . $id . '/downloadLatestDeliveryNotePdf'
            )
        );
    }

    /**
     * Download the picking list PDF for the given shipment.
     *
     * @param string $id The weclapp UUID of the shipment.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getPickingListPdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->endpoint . '/id/' . $id . '/downloadLatestPickingListPdf'
            )
        );
    }

    /**
     * Download the shipping label PDF for the given shipment.
     *
     * @param string $id The weclapp UUID of the shipment.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getShippingLabelPdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->endpoint . '/id/' . $id . '/downloadLatestShippingLabelPdf'
            )
        );
    }
}
