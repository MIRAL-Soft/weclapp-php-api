<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use InvalidArgumentException;
use miralsoft\weclapp\api\DTO\WebhookDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;

/**
 * Resource class for weclapp Webhook management.
 *
 * Allows registering, listing, updating and deleting webhook subscriptions.
 * Webhooks enable real-time event-driven integration without polling.
 *
 * Each webhook watches one entity type (entityName) and fires on any combination
 * of create / update / delete events as configured by the three boolean flags.
 *
 * The callback URL must be publicly reachable. weclapp sends a request using
 * the configured requestMethod ("GET" or "POST", default "POST") with the event
 * payload as JSON in the body.
 *
 * weclapp may automatically deactivate a webhook after repeated delivery failures;
 * check WebhookDTO::isActive() and WebhookDTO::$errorMessage for status.
 *
 * @example
 * // Subscribe to all party changes (create + update + delete)
 * $webhook = $client->webhooks()->register(
 *     entityName: 'party',
 *     url:        'https://my-app.example.com/webhooks/weclapp',
 *     atCreate:   true,
 *     atUpdate:   true,
 *     atDelete:   true,
 * );
 *
 * // Subscribe to salesOrder creates only
 * $webhook = $client->webhooks()->register(
 *     entityName: 'salesOrder',
 *     url:        'https://my-app.example.com/webhooks/weclapp',
 *     atCreate:   true,
 * );
 */
class WebhookResource extends AbstractResource
{
    protected string $endpoint = 'webhook';
    protected string $dtoClass = WebhookDTO::class;

    /**
     * Register a new webhook subscription.
     *
     * At least one of $atCreate, $atUpdate, $atDelete must be true.
     *
     * @param string $entityName     The weclapp entity type to watch (e.g. "party", "salesOrder",
     *                               "article", "salesInvoice", "quotation").
     * @param string $url            The URL to deliver events to (max 1000 chars).
     * @param bool   $atCreate       Fire when an entity of this type is created.
     * @param bool   $atUpdate       Fire when an entity of this type is updated.
     * @param bool   $atDelete       Fire when an entity of this type is deleted.
     * @param string $requestMethod  HTTP method for delivery: "GET" or "POST" (default "POST").
     *
     * @throws InvalidArgumentException If no event flag is enabled or requestMethod is invalid.
     * @throws WeclappApiException
     */
    public function register(
        string $entityName,
        string $url,
        bool   $atCreate       = false,
        bool   $atUpdate       = false,
        bool   $atDelete       = false,
        string $requestMethod  = 'POST',
    ): WebhookDTO {
        if (!$atCreate && !$atUpdate && !$atDelete) {
            throw new InvalidArgumentException(
                'At least one of $atCreate, $atUpdate, $atDelete must be true when registering a webhook.'
            );
        }

        if (!in_array($requestMethod, ['GET', 'POST'], true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid requestMethod "%s". Allowed values: "GET", "POST".', $requestMethod)
            );
        }

        return $this->create([
            'entityName'    => $entityName,
            'url'           => $url,
            'atCreate'      => $atCreate,
            'atUpdate'      => $atUpdate,
            'atDelete'      => $atDelete,
            'requestMethod' => $requestMethod,
        ]);
    }

    /**
     * Retrieve all registered webhook subscriptions.
     *
     * @return list<WebhookDTO>
     *
     * @throws WeclappApiException
     */
    public function all(): array
    {
        /** @var list<WebhookDTO> */
        return $this->listAll();
    }

    /**
     * {@inheritdoc}
     *
     * @return WebhookDTO
     */
    public function find(string $id): WebhookDTO
    {
        /** @var WebhookDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return WebhookDTO
     */
    public function create(array $data): WebhookDTO
    {
        /** @var WebhookDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return WebhookDTO
     */
    public function update(string $id, array $data): WebhookDTO
    {
        /** @var WebhookDTO */
        return parent::update($id, $data);
    }
}
