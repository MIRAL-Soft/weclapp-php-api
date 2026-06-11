<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\Client\HttpClient;
use miralsoft\weclapp\api\Client\RateLimiter;
use miralsoft\weclapp\api\DTO\PartyDTO;
use miralsoft\weclapp\api\DTO\SalesInvoiceDTO;
use miralsoft\weclapp\api\Enum\SalesInvoiceType;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use Psr\SimpleCache\CacheInterface;

/**
 * Resource class for weclapp Sales Invoice operations.
 *
 * Wraps the /api/v2/salesInvoice endpoint.
 * Includes convenience methods for downloading cancellation invoice PDFs (credit notes)
 * via the /document endpoint.
 *
 * @extends AbstractResource<\miralsoft\weclapp\api\DTO\SalesInvoiceDTO>
 */
class SalesInvoiceResource extends AbstractResource
{
    protected string $endpoint = 'salesInvoice';
    protected string $dtoClass = SalesInvoiceDTO::class;

    /** @var array<string, PartyDTO> In-memory cache: partyId → PartyDTO */
    private array $partyCache = [];

    private readonly DocumentResource $documentResource;

    public function __construct(
        HttpClient $http,
        RateLimiter $rateLimiter,
        ?CacheInterface $cache = null,
    ) {
        parent::__construct($http, $rateLimiter, $cache);
        $this->documentResource = new DocumentResource($http, $rateLimiter, $cache);
    }

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
                $this->idPath($id, '/downloadLatestSalesInvoicePdf')
            )
        );
    }

    /**
     * Resolve the customer display name for the given invoice.
     *
     * Handles the ORGANIZATION vs. PERSON distinction correctly:
     * - ORGANIZATION → company name
     * - PERSON       → "First Last"
     *
     * Resolution order:
     * 1. party/id/{customerId} lookup via the weclapp party endpoint.
     * 2. 'Unknown' if the customerId is empty or the party cannot be resolved.
     *
     * Party lookups are cached in memory for the lifetime of this resource
     * instance, so processing multiple invoices for the same customer only
     * triggers one API call.
     *
     * @throws WeclappApiException
     *
     * @example
     * foreach ($client->salesInvoices()->findOpen() as $invoice) {
     *     $name = $client->salesInvoices()->resolveCustomerDisplayName($invoice);
     *     echo $invoice->invoiceNumber . ' — ' . $name;
     * }
     */
    public function resolveCustomerDisplayName(SalesInvoiceDTO $invoice): string
    {
        if ($invoice->customerId !== '') {
            $party = $this->fetchParty($invoice->customerId);
            if ($party !== null) {
                return $party->getDisplayName();
            }
        }

        return 'Unknown';
    }

    /**
     * Fetch a PartyDTO by ID, using the in-memory cache.
     *
     * @throws WeclappApiException
     */
    private function fetchParty(string $partyId): ?PartyDTO
    {
        if (isset($this->partyCache[$partyId])) {
            return $this->partyCache[$partyId];
        }

        try {
            $data  = $this->rateLimiter->execute(
                fn () => $this->http->get('party/id/' . rawurlencode($partyId))
            );
            $party = PartyDTO::fromArray($data);
            $this->partyCache[$partyId] = $party;

            return $party;
        } catch (WeclappApiException) {
            return null;
        }
    }

    /**
     * Find a sales invoice by its human-readable invoice number (e.g. "RE-10042").
     *
     * Works for all invoice types including credit notes (CLX-prefix) and
     * proforma invoices (tenant-configured prefix, e.g. "PR-").
     *
     * @param string $invoiceNumber The invoice number shown in the weclapp UI.
     * @return SalesInvoiceDTO
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException If no invoice with that number exists.
     * @throws WeclappApiException
     */
    public function findByInvoiceNumber(string $invoiceNumber): SalesInvoiceDTO
    {
        $result = $this->list(
            QueryBuilder::new()
                ->filterEq('invoiceNumber', $invoiceNumber)
                ->pageSize(1)
        );

        if (empty($result->items)) {
            throw new \miralsoft\weclapp\api\Exception\NotFoundException(
                sprintf('Sales invoice with number "%s" not found.', $invoiceNumber)
            );
        }

        /** @var SalesInvoiceDTO */
        return $result->items[0];
    }

    /**
     * Find all credit notes across all customers.
     *
     * Credit notes are identified by salesInvoiceType = CREDIT_NOTE and carry
     * a CLX-prefixed invoiceNumber. Each credit note references its original
     * invoice via the precedingSalesInvoiceId field.
     *
     * @return list<SalesInvoiceDTO>
     *
     * @throws WeclappApiException
     *
     * @example
     * $creditNotes = $client->salesInvoices()->findCreditNotes();
     * foreach ($creditNotes as $note) {
     *     $pdf = $client->salesInvoices()->getPdf($note->id);
     *     file_put_contents($note->invoiceNumber . '.pdf', $pdf);
     * }
     */
    public function findCreditNotes(?QueryBuilder $extra = null): array
    {
        $q = clone ($extra ?? QueryBuilder::new());
        $q->filterEq('salesInvoiceType', SalesInvoiceType::CreditNote->value)
          ->sortByCreated('desc');

        /** @var list<SalesInvoiceDTO> */
        return $this->listAll($q);
    }

    /**
     * Find all credit notes modified since a given point in time.
     *
     * Convenience method for delta-sync of credit notes only.
     *
     * @param \DateTimeInterface|int $since A DateTime object or epoch milliseconds.
     * @return list<SalesInvoiceDTO>
     *
     * @throws WeclappApiException
     */
    public function findCreditNotesModifiedSince(\DateTimeInterface|int $since): array
    {
        $q = QueryBuilder::new()
            ->filterEq('salesInvoiceType', SalesInvoiceType::CreditNote->value);

        /** @var list<SalesInvoiceDTO> */
        return parent::findModifiedSince($since, $q);
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
     * Find all invoices belonging to a specific sales order.
     *
     * **weclapp filter quirk (live-verified):** the `salesOrderId` field is NOT
     * filterable — `salesOrderId-eq=…` is rejected with HTTP 400 ("unexpected
     * filter property"). The filterable path is the relation sub-property
     * `salesOrders.id`, which this method uses to filter **server-side**. This
     * keeps the request fast: the API returns only the matching invoices (e.g.
     * 1888 total → exactly 1) instead of the full collection.
     *
     * **Performance:** a single paginated query (pageSize 1000) — one HTTP
     * request for any order with ≤ 1000 invoices, which is always the case in
     * practice. No preceding count() call is made. The result is then verified
     * client-side (zero extra requests) so that a silently ignored/misunderstood
     * filter can never return foreign invoices — mirroring the "server fuzzy →
     * client exact" pattern used by other findBy* methods.
     *
     * Returns an empty list when the order has no invoices — never throws on
     * "nothing found".
     *
     * @param string $salesOrderId The weclapp UUID of the sales order.
     * @return list<SalesInvoiceDTO>
     *
     * @throws WeclappApiException
     *
     * @example
     * foreach ($client->salesInvoices()->findBySalesOrder($orderId) as $invoice) {
     *     // e.g. refresh the performance record (Leistungsnachweis) of each invoice
     * }
     */
    public function findBySalesOrder(string $salesOrderId): array
    {
        if ($salesOrderId === '') {
            return [];
        }

        $candidates = $this->listAll(
            QueryBuilder::new()
                ->filterEq('salesOrders.id', $salesOrderId)
                ->sortByCreated('desc')
        );

        // Client-side exact match: an invoice belongs to the order if its
        // salesOrderId matches or its salesOrders[] relation contains the ID.
        // Pure in-memory filtering — adds no API request.
        return array_values(array_filter(
            $candidates,
            fn (SalesInvoiceDTO $invoice): bool => $this->belongsToSalesOrder($invoice, $salesOrderId)
        ));
    }

    /**
     * Determine whether an invoice belongs to the given sales order.
     *
     * True if the invoice's `salesOrderId` equals the ID, or its `salesOrders[]`
     * relation array contains an entry with that ID.
     */
    private function belongsToSalesOrder(SalesInvoiceDTO $invoice, string $salesOrderId): bool
    {
        if ($invoice->salesOrderId === $salesOrderId) {
            return true;
        }

        foreach ($invoice->salesOrders as $relation) {
            if (is_array($relation) && ($relation['id'] ?? null) === $salesOrderId) {
                return true;
            }
        }

        return false;
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
     * Download the cancellation invoice PDF for a cancelled sales invoice.
     *
     * Retrieves the SALES_INVOICE_CANCELLATION document attached to the invoice
     * and returns its binary PDF content.
     *
     * Returns null if no cancellation document is attached — this is the case
     * for invoices that are not cancelled, or where the cancellation was processed
     * outside weclapp.
     *
     * Typical usage:
     *   1. Fetch all invoices with status CANCELLED.
     *   2. For each, call getCancellationPdf() to download the cancellation document.
     *
     * @param string $salesInvoiceId The weclapp UUID of the original (cancelled) sales invoice.
     * @return string|null Raw binary PDF content, or null if no cancellation document exists.
     *
     * @throws WeclappApiException
     *
     * @example
     * $invoices = $client->salesInvoices()->listAll(
     *     QueryBuilder::new()->filterEq('status', 'CANCELLED')
     * );
     * foreach ($invoices as $invoice) {
     *     $pdf = $client->salesInvoices()->getCancellationPdf($invoice->id);
     *     if ($pdf !== null) {
     *         file_put_contents($invoice->cancellationNumber . '.pdf', $pdf);
     *     }
     * }
     */
    public function getCancellationPdf(string $salesInvoiceId): ?string
    {
        return $this->documentResource->downloadCancellationInvoice($salesInvoiceId);
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
