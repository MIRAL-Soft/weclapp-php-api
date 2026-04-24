<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\AddressDTO;
use miralsoft\weclapp\api\DTO\CustomerDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CustomerDTO (137-field party schema).
 */
class CustomerDTOTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'id'               => 'abc-123',
            'version'          => '3',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'customerNumber'   => 'K-10042',
            'partyType'        => 'ORGANIZATION',
            'company'          => 'Acme GmbH',
            'salutation'       => 'MR',
            'firstName'        => null,
            'lastName'         => null,
            'email'            => 'info@acme.de',
            'phone'            => '+49 30 12345',
            'mobilePhone1'     => null,
            'website'          => 'https://acme.de',
            'customerBlocked'  => false,
            'customerInsolvent' => false,
            'currencyId'       => 'eur-id',
            'responsibleUserId' => 'user-1',
            'customerSalesChannel' => 'ONLINE',
            'addresses'        => [
                [
                    'id'              => 'addr-1',
                    'version'         => '1',
                    'createdDate'     => 1711400000000,
                    'lastModifiedDate' => 1711400000000,
                    'street1'         => 'Hauptstraße 1',
                    'zipcode'         => '10115',
                    'city'            => 'Berlin',
                    'countryCode'     => 'DE',
                    'primaryAddress'  => true,
                    'deliveryAddress' => false,
                    'invoiceAddress'  => false,
                ],
            ],
            'contacts'         => [],
            'tags'             => [],
            'customAttributes' => [],
        ];
    }

    public function test_creates_dto_from_array(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());

        self::assertSame('abc-123', $dto->id);
        self::assertSame('K-10042', $dto->customerNumber);
        self::assertSame('Acme GmbH', $dto->company);
        self::assertSame('info@acme.de', $dto->email);
        self::assertFalse($dto->customerBlocked);
    }

    public function test_addresses_are_hydrated_as_address_dtos(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());

        self::assertCount(1, $dto->addresses);
        self::assertInstanceOf(AddressDTO::class, $dto->addresses[0]);
        self::assertSame('Hauptstraße 1', $dto->addresses[0]->street1);
        self::assertSame('Berlin', $dto->addresses[0]->city);
    }

    public function test_get_display_name_returns_company_for_organization(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());

        self::assertSame('Acme GmbH', $dto->getDisplayName());
    }

    public function test_get_display_name_returns_full_name_for_person(): void
    {
        $data              = $this->sampleData();
        $data['partyType'] = 'PERSON';
        $data['company']   = null;
        $data['firstName'] = 'Max';
        $data['lastName']  = 'Mustermann';

        $dto = CustomerDTO::fromArray($data);

        self::assertSame('Max Mustermann', $dto->getDisplayName());
    }

    public function test_get_display_name_ignores_company_field_for_person(): void
    {
        // A PERSON customer with a non-null company field (their employer).
        // The old implementation returned the company name here — semantically wrong.
        $data              = $this->sampleData();
        $data['partyType'] = 'PERSON';
        $data['company']   = 'Employer GmbH'; // employer, not the person's display name
        $data['firstName'] = 'Anna';
        $data['lastName']  = 'Schmidt';

        $dto = CustomerDTO::fromArray($data);

        self::assertSame('Anna Schmidt', $dto->getDisplayName());
    }

    public function test_get_display_name_falls_back_to_customer_number_for_organization_without_company(): void
    {
        $data            = $this->sampleData();
        $data['company'] = null;

        $dto = CustomerDTO::fromArray($data);

        self::assertSame('K-10042', $dto->getDisplayName());
    }

    public function test_get_display_name_falls_back_to_customer_number_for_person_without_name(): void
    {
        $data               = $this->sampleData();
        $data['partyType']  = 'PERSON';
        $data['company']    = null;
        $data['firstName']  = null;
        $data['lastName']   = null;

        $dto = CustomerDTO::fromArray($data);

        self::assertSame('K-10042', $dto->getDisplayName());
    }

    public function test_get_created_at_returns_datetime(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());
        $dt  = $dto->getCreatedAt();

        self::assertNotNull($dt);
        self::assertSame(1711400000, $dt->getTimestamp());
    }

    public function test_get_last_modified_at_returns_datetime(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());

        self::assertNotNull($dto->getLastModifiedAt());
        self::assertSame(1711450000, $dto->getLastModifiedAt()->getTimestamp());
    }

    public function test_handles_missing_optional_fields_gracefully(): void
    {
        $dto = CustomerDTO::fromArray([
            'id'              => 'minimal-id',
            'version'         => '1',
            'createdDate'     => 0,
            'lastModifiedDate' => 0,
        ]);

        self::assertSame('minimal-id', $dto->id);
        self::assertNull($dto->email);
        self::assertNull($dto->phone);
        self::assertNull($dto->company);
        self::assertSame([], $dto->addresses);
        self::assertFalse($dto->customerBlocked);
    }

    public function test_is_blocked_returns_false_by_default(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());

        self::assertFalse($dto->isBlocked());
    }

    public function test_is_blocked_returns_true_when_customer_blocked(): void
    {
        $data                   = $this->sampleData();
        $data['customerBlocked'] = true;

        $dto = CustomerDTO::fromArray($data);

        self::assertTrue($dto->isBlocked());
    }
}
