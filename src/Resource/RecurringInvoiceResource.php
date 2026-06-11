<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\RecurringInvoiceDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Recurring Invoice operations (read-only).
 *
 * Wraps the `/api/v2/recurringInvoice` endpoint. A recurring invoice is the
 * template weclapp uses to generate sales invoices at a fixed interval — the
 * authoritative source for a managed-service contract's billing cadence and the
 * billed positions/quantities/amounts per customer.
 *
 * **Read-only by design:** the weclapp API exposes only GET/HEAD on this endpoint
 * (verified live: POST/PUT/DELETE all return HTTP 405, `Allow: GET, HEAD, OPTIONS`).
 * This resource therefore deliberately offers no create/update/delete — calling
 * them would always fail server-side. Inherited write methods are overridden to
 * throw a clear \LogicException instead of issuing a doomed request.
 *
 * Note: this endpoint is not described in the published OpenAPI document but is
 * fully functional on live tenants (verified against miralsoft — 389 records).
 *
 * @example List the billing interval of every recurring invoice:
 * ```php
 * foreach ($client->recurringInvoices()->listAll() as $ri) {
 *     echo $ri->recurringInvoiceNumber
 *        . ': ' . $ri->getCadenceLabel()                       // "every 1 MONTHLY"
 *        . ', next ' . $ri->getNextInvoiceDate()?->format('Y-m-d')
 *        . PHP_EOL;
 * }
 * ```
 *
 * @example Webhook (Trigger → Read): when a recurringInvoice webhook fires,
 * re-read the entity by the id from the payload:
 * ```php
 * // WebhookEntityName::RecurringInvoice → $payload['entityId']
 * $ri = $client->recurringInvoices()->find($payload['entityId']);
 * ```
 *
 * @see \miralsoft\weclapp\api\DTO\RecurringInvoiceDTO
 * @see \miralsoft\weclapp\api\Enum\WebhookEntityName::RecurringInvoice
 *
 * @extends AbstractResource<\miralsoft\weclapp\api\DTO\RecurringInvoiceDTO>
 */
class RecurringInvoiceResource extends AbstractResource
{
    protected string $endpoint = 'recurringInvoice';
    protected string $dtoClass = RecurringInvoiceDTO::class;

    /**
     * {@inheritdoc}
     *
     * @return RecurringInvoiceDTO
     */
    public function find(string $id): RecurringInvoiceDTO
    {
        /** @var RecurringInvoiceDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<RecurringInvoiceDTO>
     */
    public function listAll(?QueryBuilder $query = null): array
    {
        /** @var list<RecurringInvoiceDTO> */
        return parent::listAll($query);
    }

    /**
     * Find all recurring invoices for a specific customer.
     *
     * @param string $customerId The weclapp UUID of the customer.
     * @return list<RecurringInvoiceDTO>
     *
     * @throws WeclappApiException
     */
    public function findByCustomer(string $customerId): array
    {
        if ($customerId === '') {
            return [];
        }

        /** @var list<RecurringInvoiceDTO> */
        return $this->listAll(
            QueryBuilder::new()
                ->filterEq('customerId', $customerId)
                ->sortByCreated('desc')
        );
    }

    /**
     * Find a recurring invoice by its human-readable number (e.g. "1001").
     *
     * @return RecurringInvoiceDTO|null Null if no recurring invoice has that number.
     *
     * @throws WeclappApiException
     */
    public function findByNumber(string $recurringInvoiceNumber): ?RecurringInvoiceDTO
    {
        $result = $this->list(
            QueryBuilder::new()
                ->filterEq('recurringInvoiceNumber', $recurringInvoiceNumber)
                ->pageSize(1)
        );

        /** @var RecurringInvoiceDTO|null */
        return $result->items[0] ?? null;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<RecurringInvoiceDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<RecurringInvoiceDTO> */
        return parent::findModifiedSince($since, $extra);
    }

    // -------------------------------------------------------------------------
    // Write operations are unsupported by the weclapp API (HTTP 405)
    // -------------------------------------------------------------------------

    /**
     * @throws \LogicException Always — the recurringInvoice endpoint is read-only (HTTP 405).
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): RecurringInvoiceDTO
    {
        throw new \LogicException(
            'The weclapp recurringInvoice endpoint is read-only (HTTP 405 on POST). '
            . 'Recurring invoices cannot be created via the API.'
        );
    }

    /**
     * @throws \LogicException Always — the recurringInvoice endpoint is read-only (HTTP 405).
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): RecurringInvoiceDTO
    {
        throw new \LogicException(
            'The weclapp recurringInvoice endpoint is read-only (HTTP 405 on PUT). '
            . 'Recurring invoices cannot be updated via the API.'
        );
    }

    /**
     * @throws \LogicException Always — the recurringInvoice endpoint is read-only (HTTP 405).
     */
    public function delete(string $id): void
    {
        throw new \LogicException(
            'The weclapp recurringInvoice endpoint is read-only (HTTP 405 on DELETE). '
            . 'Recurring invoices cannot be deleted via the API.'
        );
    }
}
