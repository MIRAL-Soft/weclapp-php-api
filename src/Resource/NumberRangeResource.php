<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\NumberRangeDTO;
use miralsoft\weclapp\api\Enum\NumberRangeType;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Number Range operations.
 *
 * Number ranges configure the document numbering series for each entity type
 * in weclapp (e.g. invoices "RE-", credit notes "CLX-", proforma "PR-").
 * The actual prefix and counter live in associated NumberRangeValue records.
 *
 * This endpoint is read-only in the API (GET only).
 *
 * Key use case — detecting proforma invoices for DATEV:
 *   Proforma invoices have no dedicated salesInvoiceType value. They are
 *   identified solely by their invoiceNumber prefix, which is tenant-specific
 *   and configurable. Use getProformaInvoicePrefix() to fetch it at runtime:
 *
 *   $prefix   = $client->numberRanges()->getProformaInvoicePrefix(); // e.g. "PR-"
 *   $forDatev = array_filter(
 *       $client->salesInvoices()->listAll(),
 *       fn($inv) => $prefix === null || !str_starts_with($inv->invoiceNumber, $prefix)
 *   );
 *
 * Wraps the /api/v2/numberRange endpoint.
 *
 * @see \miralsoft\weclapp\api\DTO\NumberRangeDTO
 * @see \miralsoft\weclapp\api\Resource\NumberRangeValueResource
 * @see \miralsoft\weclapp\api\Enum\NumberRangeType
 */
class NumberRangeResource extends AbstractResource
{
    protected string $endpoint = 'numberRange';
    protected string $dtoClass = NumberRangeDTO::class;

    /**
     * Retrieve a single NumberRange by its weclapp ID.
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException
     * @throws WeclappApiException
     */
    public function find(string $id): NumberRangeDTO
    {
        /** @var NumberRangeDTO */
        return parent::find($id);
    }

    /**
     * Find the NumberRange configured for the given entity type.
     *
     * Returns null if no range is configured for the given type
     * (which would be unusual in a properly set-up weclapp instance).
     *
     * @param NumberRangeType|string $type A NumberRangeType enum case or its raw string value.
     *
     * @throws WeclappApiException
     *
     * @example
     * $range = $client->numberRanges()->findByType(NumberRangeType::ProformaInvoice);
     * $range = $client->numberRanges()->findByType('PROFORMA_INVOICE'); // also valid
     */
    public function findByType(NumberRangeType|string $type): ?NumberRangeDTO
    {
        $typeValue = $type instanceof NumberRangeType ? $type->value : $type;

        $result = $this->list(
            QueryBuilder::new()->filterEq('type', $typeValue)->pageSize(1)
        );

        /** @var NumberRangeDTO|null */
        return $result->items[0] ?? null;
    }

    /**
     * Returns the invoice number prefix configured for proforma invoices.
     *
     * Makes two sequential API calls:
     *   1. GET /numberRange?type-eq=PROFORMA_INVOICE  — finds the range
     *   2. GET /numberRangeValue?numberRangeId-eq={id} — reads the prefix
     *
     * Returns null if weclapp has no proforma number range configured,
     * or if the range has no prefix set.
     *
     * Cache this result — the prefix rarely changes and the extra API calls
     * on every sync run add unnecessary latency.
     *
     * @throws WeclappApiException
     *
     * @example
     * $prefix   = $client->numberRanges()->getProformaInvoicePrefix(); // "PR-"
     * $forDatev = array_filter(
     *     $invoices,
     *     fn($inv) => $prefix === null || !str_starts_with($inv->invoiceNumber, $prefix)
     * );
     */
    public function getProformaInvoicePrefix(): ?string
    {
        $range = $this->findByType(NumberRangeType::ProformaInvoice);

        if ($range === null) {
            return null;
        }

        $valueResource = new NumberRangeValueResource(
            $this->http,
            $this->rateLimiter,
            $this->cache,
        );

        return $valueResource->findPrefix($range->id);
    }
}
