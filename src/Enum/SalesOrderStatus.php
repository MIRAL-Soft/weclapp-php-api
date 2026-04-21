<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Known status values for weclapp Sales Orders.
 *
 * @example
 * if ($order->status === SalesOrderStatus::Confirmed->value) { ... }
 *
 * // Or compare directly using the enum:
 * if (SalesOrderStatus::tryFrom($order->status) === SalesOrderStatus::Confirmed) { ... }
 */
enum SalesOrderStatus: string
{
    case OrderInProcess = 'ORDER_IN_PROCESS';
    case Confirmed      = 'ORDER_CONFIRMED';
    case Shipped        = 'ORDER_SHIPPED';
    case Delivered      = 'ORDER_DELIVERED';
    case Invoiced       = 'ORDER_INVOICED';
    case Cancelled      = 'CANCELLED';
    case Closed         = 'CLOSED';
}
