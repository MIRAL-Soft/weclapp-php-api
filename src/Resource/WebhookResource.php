<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\WebhookDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;

/**
 * Resource class for weclapp Webhook management.
 *
 * Allows registering, listing and deleting webhook subscriptions.
 * Webhooks enable real-time event-driven integration without polling.
 *
 * Supported event types:
 *   - article.created / article.updated / article.deleted
 *   - salesOrder.created / salesOrder.updated / salesOrder.deleted
 *   - salesInvoice.created / salesInvoice.updated
 *   - quotation.created / quotation.updated
 *   - party.created / party.updated / party.deleted  (customers, contacts, suppliers)
 *
 * The callback URL must be publicly reachable via HTTPS.
 * weclapp sends a POST request with the event payload as JSON.
 *
 * @example
 * $webhook = $client->webhooks()->register(
 *     eventType:   'party.updated',
 *     callbackUrl: 'https://my-app.example.com/webhooks/weclapp',
 *     description: 'Sync customer changes to DocBee'
 * );
 */
class WebhookResource extends AbstractResource
{
    protected string $endpoint = 'webhook';
    protected string $dtoClass = WebhookDTO::class;

    /**
     * Register a new webhook subscription.
     *
     * @param string      $eventType   The event type to subscribe to (e.g. "party.updated").
     * @param string      $callbackUrl The HTTPS URL to deliver events to.
     * @param string|null $description Optional human-readable description.
     *
     * @throws WeclappApiException
     */
    public function register(string $eventType, string $callbackUrl, ?string $description = null): WebhookDTO
    {
        $data = [
            'eventType'   => $eventType,
            'callbackUrl' => $callbackUrl,
            'active'      => true,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        return $this->create($data);
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
