<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\QuotationDTO;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Quotation (Angebot) operations.
 *
 * Wraps the /api/v2/quotation endpoint.
 * Includes PDF download and conversion to sales order.
 */
class QuotationResource extends AbstractResource
{
    protected string $endpoint = 'quotation';
    protected string $dtoClass = QuotationDTO::class;

    /**
     * Download the PDF for the given quotation.
     *
     * @param string $id The weclapp UUID of the quotation.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getPdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->endpoint . '/id/' . $id . '/downloadLatestQuotationPdf'
            )
        );
    }

    /**
     * Convert a quotation to a sales order.
     *
     * Triggers the weclapp "createSalesOrder" action on the quotation.
     * The original quotation status is updated to QUOTATION_ACCEPTED.
     *
     * @param string $id The weclapp UUID of the quotation to convert.
     * @return SalesOrderDTO The newly created sales order.
     *
     * @throws WeclappApiException
     */
    public function convertToSalesOrder(string $id): SalesOrderDTO
    {
        $response = $this->rateLimiter->execute(
            fn () => $this->http->post(
                $this->endpoint . '/id/' . $id . '/createSalesOrder',
                []
            )
        );

        return SalesOrderDTO::fromArray($response);
    }

    /**
     * Find all quotations for a specific customer.
     *
     * @param string $customerId The weclapp UUID of the customer.
     * @return list<QuotationDTO>
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

        /** @var list<QuotationDTO> */
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return QuotationDTO
     */
    public function find(string $id): QuotationDTO
    {
        /** @var QuotationDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return QuotationDTO
     */
    public function create(array $data): QuotationDTO
    {
        /** @var QuotationDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return QuotationDTO
     */
    public function update(string $id, array $data): QuotationDTO
    {
        /** @var QuotationDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<QuotationDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<QuotationDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
