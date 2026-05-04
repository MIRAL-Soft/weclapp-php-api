<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use miralsoft\weclapp\api\DTO\SalesOrderItemDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\OptimisticLockException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Sales Order operations.
 *
 * Wraps the /api/v2/salesOrder endpoint.
 * Includes PDF download and delivery creation capabilities.
 */
class SalesOrderResource extends AbstractResource
{
    protected string $endpoint = 'salesOrder';
    protected string $dtoClass = SalesOrderDTO::class;

    /**
     * Download the order confirmation PDF for the given order.
     *
     * @param string $id The weclapp UUID of the sales order.
     * @return string Raw binary PDF content.
     *
     * @throws WeclappApiException
     */
    public function getPdf(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->endpoint . '/id/' . $id . '/downloadLatestOrderConfirmationPdf'
            )
        );
    }

    /**
     * Find all orders for a specific customer.
     *
     * @param string $customerId The weclapp UUID of the customer.
     * @return list<SalesOrderDTO>
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

        /** @var list<SalesOrderDTO> */
        return $result;
    }

    /**
     * Find all orders with a specific status.
     *
     * Common status values: ORDER_ENTRY_IN_PROGRESS, ORDER_CONFIRMED,
     * DELIVERY_NOTE_CREATED, INVOICE_CREATED, ORDER_CANCELLED.
     *
     * @return list<SalesOrderDTO>
     *
     * @throws WeclappApiException
     */
    public function findByStatus(string $status): array
    {
        $result = $this->listAll(
            QueryBuilder::new()->filterEq('status', $status)
        );

        /** @var list<SalesOrderDTO> */
        return $result;
    }

    /**
     * Add a new line item to an existing sales order.
     *
     * Uses a Read-Modify-Write pattern: the order is fetched first to obtain the
     * current version and full item list, then re-submitted via PUT with the new
     * item appended. The current version is included automatically for optimistic
     * locking — if another process modified the order concurrently, an
     * OptimisticLockException is thrown and the caller must re-fetch and retry.
     *
     * At least one of "articleId" or "title" must be present in $data.
     * All other fields (quantity, unitPrice, taxId, etc.) are optional — weclapp
     * fills defaults from the article master record when articleId is given.
     *
     * @param string               $orderId The weclapp UUID of the sales order.
     * @param array<string, mixed> $data    Fields for the new item.
     * @return SalesOrderDTO The updated order returned by weclapp after the PUT.
     *
     * @throws \InvalidArgumentException  If neither "articleId" nor "title" is provided.
     * @throws NotFoundException          If the order does not exist.
     * @throws OptimisticLockException    If the order was concurrently modified (HTTP 409).
     * @throws WeclappApiException
     */
    public function addOrderItem(string $orderId, array $data): SalesOrderDTO
    {
        if (empty($data['articleId']) && empty($data['title'])) {
            throw new \InvalidArgumentException(
                'addOrderItem() requires either "articleId" or "title" in $data.'
            );
        }

        $order = $this->find($orderId);

        $items   = array_map(static fn (SalesOrderItemDTO $item): array => $item->toArray(), $order->orderItems);
        $items[] = $data;

        /** @var SalesOrderDTO */
        return $this->update($orderId, [
            'version'    => $order->version,
            'orderItems' => $items,
        ]);
    }

    /**
     * Update an existing line item within a sales order.
     *
     * Uses a Read-Modify-Write pattern. The existing item identified by $itemId is
     * located in the order, the caller-supplied $data is merged on top of it (shallow
     * merge), and the full updated item list is PUT back to weclapp.
     *
     * Only the fields present in $data are changed; all other item fields retain
     * their current values from the fetched order.
     *
     * @param string               $orderId The weclapp UUID of the sales order.
     * @param string               $itemId  The weclapp UUID of the item to update.
     * @param array<string, mixed> $data    Fields to change on the item.
     * @return SalesOrderDTO The updated order returned by weclapp after the PUT.
     *
     * @throws NotFoundException       If the order or the item does not exist.
     * @throws OptimisticLockException If the order was concurrently modified (HTTP 409).
     * @throws WeclappApiException
     */
    public function updateOrderItem(string $orderId, string $itemId, array $data): SalesOrderDTO
    {
        $order = $this->find($orderId);

        $found = false;
        $items = array_map(
            static function (SalesOrderItemDTO $item) use ($itemId, $data, &$found): array {
                $itemArray = $item->toArray();
                if ($item->id === $itemId) {
                    $found     = true;
                    $itemArray = array_merge($itemArray, $data);
                }
                return $itemArray;
            },
            $order->orderItems,
        );

        if (!$found) {
            throw new NotFoundException(
                sprintf('Order item "%s" not found in sales order "%s".', $itemId, $orderId)
            );
        }

        /** @var SalesOrderDTO */
        return $this->update($orderId, [
            'version'    => $order->version,
            'orderItems' => $items,
        ]);
    }

    /**
     * Remove a line item from a sales order.
     *
     * Uses a Read-Modify-Write pattern. The item identified by $itemId is filtered
     * out of the current item list, and the remaining items are PUT back to weclapp.
     *
     * @param string $orderId The weclapp UUID of the sales order.
     * @param string $itemId  The weclapp UUID of the item to remove.
     * @return SalesOrderDTO The updated order returned by weclapp after the PUT.
     *
     * @throws NotFoundException       If the order does not exist or the item is not found.
     * @throws OptimisticLockException If the order was concurrently modified (HTTP 409).
     * @throws WeclappApiException
     */
    public function removeOrderItem(string $orderId, string $itemId): SalesOrderDTO
    {
        $order = $this->find($orderId);

        $items  = array_map(static fn (SalesOrderItemDTO $item): array => $item->toArray(), $order->orderItems);
        $before = count($items);
        $items  = array_values(array_filter($items, static fn (array $i): bool => ($i['id'] ?? null) !== $itemId));

        if (count($items) === $before) {
            throw new NotFoundException(
                sprintf('Order item "%s" not found in sales order "%s".', $itemId, $orderId)
            );
        }

        /** @var SalesOrderDTO */
        return $this->update($orderId, [
            'version'    => $order->version,
            'orderItems' => $items,
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesOrderDTO
     */
    public function find(string $id): SalesOrderDTO
    {
        /** @var SalesOrderDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesOrderDTO
     */
    public function create(array $data): SalesOrderDTO
    {
        /** @var SalesOrderDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return SalesOrderDTO
     */
    public function update(string $id, array $data): SalesOrderDTO
    {
        /** @var SalesOrderDTO */
        return parent::update($id, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<SalesOrderDTO>
     */
    public function findModifiedSince(\DateTimeInterface|int $since, ?QueryBuilder $extra = null): array
    {
        /** @var list<SalesOrderDTO> */
        return parent::findModifiedSince($since, $extra);
    }
}
