<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\RecordAddressDTO;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SalesOrderDTO.
 */
class SalesOrderDTOTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'id'               => 'order-1',
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'orderNumber'      => 'SO-10042',
            'status'           => 'ORDER_CONFIRMED',
            'customerId'       => 'cust-1',
            'orderDate'        => 1711400000000,
            'orderItems'       => [],
            'tags'             => [],
            'customAttributes' => [],
        ];
    }

    public function test_creates_from_minimal_data(): void
    {
        $dto = SalesOrderDTO::fromArray($this->sampleData());

        self::assertSame('order-1', $dto->id);
        self::assertSame('SO-10042', $dto->orderNumber);
        self::assertSame('cust-1', $dto->customerId);
        self::assertSame('ORDER_CONFIRMED', $dto->status);
    }

    public function test_removed_fields_do_not_exist(): void
    {
        // These fields were removed because they are not in the salesOrder API schema.
        // The correct field for the customer's reference is $orderNumberAtCustomer.
        $dto = SalesOrderDTO::fromArray($this->sampleData());

        self::assertFalse(property_exists($dto, 'customerNumber'));
        self::assertFalse(property_exists($dto, 'customerName'));
        self::assertFalse(property_exists($dto, 'customerOrderNumber'));
    }

    public function test_order_number_at_customer_is_preserved(): void
    {
        $data                       = $this->sampleData();
        $data['orderNumberAtCustomer'] = 'EXT-REF-99';

        $dto = SalesOrderDTO::fromArray($data);

        self::assertSame('EXT-REF-99', $dto->orderNumberAtCustomer);
    }

    public function test_delivery_address_is_hydrated_as_record_address_dto(): void
    {
        $data                   = $this->sampleData();
        $data['deliveryAddress'] = [
            'street1'     => 'Lieferweg 5',
            'city'        => 'Hamburg',
            'countryCode' => 'DE',
            'zipcode'     => '20095',
        ];

        $dto = SalesOrderDTO::fromArray($data);

        self::assertInstanceOf(RecordAddressDTO::class, $dto->deliveryAddress);
        self::assertSame('Lieferweg 5', $dto->deliveryAddress->street1);
        self::assertSame('Hamburg', $dto->deliveryAddress->city);
    }

    public function test_delivery_address_is_null_when_absent(): void
    {
        $dto = SalesOrderDTO::fromArray($this->sampleData());

        self::assertNull($dto->deliveryAddress);
        self::assertNull($dto->invoiceAddress);
        self::assertNull($dto->recordAddress);
    }

    public function test_get_order_date_returns_datetime(): void
    {
        $dto = SalesOrderDTO::fromArray($this->sampleData());

        self::assertSame(1711400000, $dto->getOrderDate()->getTimestamp());
    }

    public function test_is_fully_fulfilled_requires_all_three_flags(): void
    {
        $data              = $this->sampleData();
        $data['invoiced']  = true;
        $data['shipped']   = true;
        $data['paid']      = true;

        self::assertTrue(SalesOrderDTO::fromArray($data)->isFullyFulfilled());

        $data['paid'] = false;
        self::assertFalse(SalesOrderDTO::fromArray($data)->isFullyFulfilled());
    }

    public function test_get_net_amount_returns_float(): void
    {
        $data               = $this->sampleData();
        $data['netAmount']  = '1234.56';

        $dto = SalesOrderDTO::fromArray($data);

        self::assertSame(1234.56, $dto->getNetAmount());
    }
}
