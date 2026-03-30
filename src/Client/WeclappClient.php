<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Client;

use GuzzleHttp\Client as GuzzleClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\Resource\ArticleCategoryResource;
use miralsoft\weclapp\api\Resource\ArticleResource;
use miralsoft\weclapp\api\Resource\ContactResource;
use miralsoft\weclapp\api\Resource\CustomerResource;
use miralsoft\weclapp\api\Resource\DocumentResource;
use miralsoft\weclapp\api\Resource\PartyResource;
use miralsoft\weclapp\api\Resource\QuotationResource;
use miralsoft\weclapp\api\Resource\SalesInvoiceResource;
use miralsoft\weclapp\api\Resource\SalesOrderResource;
use miralsoft\weclapp\api\Resource\SupplierResource;
use miralsoft\weclapp\api\Resource\WebhookResource;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Main entry point for the weclapp PHP API client.
 *
 * Creates and returns all available API resource classes.
 * Inject a PSR-16 cache to enable listAll() caching.
 * Inject a PSR-3 logger to enable request/response tracing.
 *
 * @example Basic usage:
 * $config = new WeclappConfig(tenant: 'miralsoft', token: 'your-token');
 * $client = new WeclappClient($config);
 *
 * $customers = $client->customers()->listAll();
 * $article   = $client->articles()->findByArticleNumber('ART-001');
 *
 * @example From environment variables:
 * $client = new WeclappClient(WeclappConfig::fromEnv());
 *
 * @example With PSR-16 cache (e.g. Symfony Cache):
 * $cache  = new FilesystemAdapter();
 * $client = new WeclappClient($config, cache: $cache);
 *
 * @example With PSR-3 logger (e.g. Monolog):
 * $logger = new Logger('weclapp');
 * $client = new WeclappClient($config, logger: $logger);
 *
 * @example Delta-sync (fetch only changed records since last run):
 * $changed = $client->customers()->findModifiedSince($lastSyncTimestampMs);
 *
 * @example With a custom Guzzle client (e.g. for testing):
 * $mock   = new MockHandler([new Response(200, [], json_encode($fixture))]);
 * $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
 * $client = new WeclappClient($config, guzzle: $guzzle);
 */
final class WeclappClient
{
    private readonly HttpClient  $http;
    private readonly RateLimiter $rateLimiter;

    /**
     * @param WeclappConfig        $config The API configuration (tenant, token, version).
     * @param CacheInterface|null  $cache  Optional PSR-16 cache for listAll() results.
     *                                     Pass any PSR-16 compliant cache implementation
     *                                     (e.g. Symfony Cache, Laravel Cache, Doctrine Cache).
     * @param GuzzleClient|null    $guzzle Optional Guzzle HTTP client for injection.
     *                                     Defaults to a new client built from $config.
     *                                     Pass a mock client for unit testing.
     * @param LoggerInterface|null $logger Optional PSR-3 logger for request/response tracing.
     *                                     Useful for debugging API calls in development.
     */
    public function __construct(
        WeclappConfig                  $config,
        private readonly ?CacheInterface $cache  = null,
        ?GuzzleClient                  $guzzle = null,
        ?LoggerInterface               $logger = null,
    ) {
        $this->http        = new HttpClient($config, $guzzle, $logger);
        $this->rateLimiter = new RateLimiter($config->getMaxRetries());
    }

    /**
     * Returns the Party resource for resolving partyId references.
     *
     * The party endpoint is the common base entity for customers, suppliers
     * and contacts. Use this to resolve a partyId (e.g. from a salesInvoice)
     * to its customer number and display name without loading the full record.
     *
     * Endpoint: /api/v2/party
     *
     * @example Resolve a partyId from an invoice:
     * $party = $client->parties()->find($invoice->partyId);
     * echo $party->customerNumber; // e.g. "K-10042"
     */
    public function parties(): PartyResource
    {
        return new PartyResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Customer resource for CRUD and sync operations.
     *
     * Endpoint: /api/v2/customer
     *
     * @example
     * $customer = $client->customers()->find('abc123');
     * $changed  = $client->customers()->findModifiedSince($since);
     */
    public function customers(): CustomerResource
    {
        return new CustomerResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Contact resource for CRUD operations.
     *
     * Endpoint: /api/v2/contact
     *
     * @example
     * $contacts = $client->contacts()->findByCustomer($customerId);
     */
    public function contacts(): ContactResource
    {
        return new ContactResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Supplier resource for CRUD operations.
     *
     * Endpoint: /api/v2/supplier
     */
    public function suppliers(): SupplierResource
    {
        return new SupplierResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Article resource for CRUD operations.
     *
     * Endpoint: /api/v2/article
     *
     * @example
     * $article = $client->articles()->findByArticleNumber('ART-001');
     */
    public function articles(): ArticleResource
    {
        return new ArticleResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Article Category resource for CRUD operations.
     *
     * Endpoint: /api/v2/articleCategory
     */
    public function articleCategories(): ArticleCategoryResource
    {
        return new ArticleCategoryResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Sales Order resource for CRUD and PDF operations.
     *
     * Endpoint: /api/v2/salesOrder
     *
     * @example
     * $pdf = $client->salesOrders()->getPdf($orderId);
     * file_put_contents('order.pdf', $pdf);
     */
    public function salesOrders(): SalesOrderResource
    {
        return new SalesOrderResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Document resource for querying, downloading and uploading attachments.
     *
     * Documents are files attached to weclapp entities (invoices, orders, customers, etc.).
     * They are not embedded in those entities — use findByEntity() to query them.
     *
     * Endpoint: /api/v2/document
     *
     * @example Find and download the cancellation invoice PDF:
     * $pdf = $client->documents()->downloadCancellationInvoice($invoiceId);
     * file_put_contents('CLX-1061.pdf', $pdf);
     *
     * @example List all documents attached to an invoice:
     * $docs = $client->documents()->findByEntity($invoiceId, 'salesInvoice');
     */
    public function documents(): DocumentResource
    {
        return new DocumentResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Sales Invoice resource for CRUD and PDF operations.
     *
     * Endpoint: /api/v2/salesInvoice
     */
    public function salesInvoices(): SalesInvoiceResource
    {
        return new SalesInvoiceResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Quotation resource for CRUD and conversion operations.
     *
     * Endpoint: /api/v2/quotation
     *
     * @example
     * $order = $client->quotations()->convertToSalesOrder($quotationId);
     */
    public function quotations(): QuotationResource
    {
        return new QuotationResource($this->http, $this->rateLimiter, $this->cache);
    }

    /**
     * Returns the Webhook resource for managing event subscriptions.
     *
     * Endpoint: /api/v2/webhook
     *
     * @example
     * $client->webhooks()->register('party.updated', 'https://my-app.com/weclapp-events');
     */
    public function webhooks(): WebhookResource
    {
        return new WebhookResource($this->http, $this->rateLimiter, $this->cache);
    }
}
