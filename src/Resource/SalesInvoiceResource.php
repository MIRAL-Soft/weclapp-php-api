<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\SalesInvoiceDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Sales Invoice operations.
 *
 * Wraps the /api/v2/salesInvoice endpoint.
 */
class SalesInvoiceResource extends AbstractResource
{
    protected string $endpoint = 'salesInvoice';
    protected string $dtoClass = SalesInvoiceDTO::class;

    /**
     * Download the PDF for the given sales invoice.
     *
     * @param string $id The weclapp UUID of the sales invoice.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getPdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->endpoint . '/' . $id . '/downloadLatestSalesInvoicePdf'
            )
        );
    }

    /**
     * Find all invoices for a specific customer.
     *
     * @param string $customerId The weclapp UUID of the customer.
     * @return list<SalesInvoiceDTO>
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

        /** @var list<SalesInvoiceDTO> */
        return $result;
    }

    /**
     * Find all open (unpaid) invoices.
     *
     * @return list<SalesInvoiceDTO>
     *
     * @throws WeclappApiException
     */
    public function findOpen(): array
    {
        $result = $this->listAll(
            QueryBuilder::new()->filterGt('openAmount', 0)
        );

        /** @var list<SalesInvoiceDTO> */
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesInvoiceDTO
     */
    public function find(string $id): SalesInvoiceDTO
    {
        /** @var SalesInvoiceDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesInvoiceDTO
     */
    public function create(array $data): SalesInvoiceDTO
    {
        /** @var SalesInvoiceDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesInvoiceDTO
     */
    public function update(string $id, array $data): SalesInvoiceDTO
    {
        /** @var SalesInvoiceDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<SalesInvoiceDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<SalesInvoiceDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
