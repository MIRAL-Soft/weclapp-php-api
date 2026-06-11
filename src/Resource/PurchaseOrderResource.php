<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\PurchaseOrderDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Purchase Order operations.
 *
 * Wraps the /api/v2/purchaseOrder endpoint. Purchase orders are sent to
 * suppliers to order goods or services. They link to incoming goods records
 * on delivery and to purchase invoices for payment.
 *
 * @see \miralsoft\weclapp\api\DTO\PurchaseOrderDTO
 *
 * @extends AbstractResource<\miralsoft\weclapp\api\DTO\PurchaseOrderDTO>
 */
class PurchaseOrderResource extends AbstractResource
{
    protected string $endpoint = 'purchaseOrder';
    protected string $dtoClass = PurchaseOrderDTO::class;

    /**
     * Retrieve all purchase orders.
     *
     * @param QueryBuilder|null $query Optional filter / sort / pagination.
     * @return list<PurchaseOrderDTO>
     *
     * @throws WeclappApiException
     */
    public function all(?QueryBuilder $query = null): array
    {
        /** @var list<PurchaseOrderDTO> */
        return $this->listAll($query);
    }

    /**
     * {@inheritdoc}
     *
     * @return PurchaseOrderDTO
     */
    public function find(string $id): PurchaseOrderDTO
    {
        /** @var PurchaseOrderDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return PurchaseOrderDTO
     */
    public function create(array $data): PurchaseOrderDTO
    {
        /** @var PurchaseOrderDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return PurchaseOrderDTO
     */
    public function update(string $id, array $data): PurchaseOrderDTO
    {
        /** @var PurchaseOrderDTO */
        return parent::update($id, $data);
    }

    /**
     * Find all purchase orders for a specific supplier.
     *
     * @param string $supplierId The weclapp UUID of the supplier.
     * @return list<PurchaseOrderDTO>
     *
     * @throws WeclappApiException
     */
    public function findBySupplier(string $supplierId): array
    {
        $result = $this->listAll(
            QueryBuilder::new()
                ->filterEq('supplierId', $supplierId)
                ->sortByCreated('desc')
        );

        /** @var list<PurchaseOrderDTO> */
        return $result;
    }

    /**
     * Find all purchase orders linked to a specific sales order (dropshipping).
     *
     * @param string $salesOrderId The weclapp UUID of the sales order.
     * @return list<PurchaseOrderDTO>
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

        /** @var list<PurchaseOrderDTO> */
        return $result;
    }

    /**
     * Download the purchase order PDF for the given order.
     *
     * @param string $id The weclapp UUID of the purchase order.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getPdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->idPath($id, '/downloadLatestPurchaseOrderPdf')
            )
        );
    }

    /**
     * Download the cancellation slip PDF for the given purchase order.
     *
     * @param string $id The weclapp UUID of the purchase order.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getCancellationSlipPdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->idPath($id, '/downloadLatestCancellationSlipPdf')
            )
        );
    }
}
