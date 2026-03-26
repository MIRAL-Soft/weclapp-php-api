<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\CustomerDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CustomerDTO.
 */
class CustomerDTOTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'id'                      => 'abc-123',
            'version'                 => '3',
            'createdDate'             => 1711400000000,
            'lastModifiedDate'        => 1711450000000,
            'customerNumber'          => 'K-10042',
            'partyType'               => 'ORGANIZATION',
            'company'                 => 'Acme GmbH',
            'salutation'              => 'MR',
            'title'                   => null,
            'firstName'               => null,
            'lastName'                => null,
            'email'                   => 'info@acme.de',
            'phone'                   => '+49 30 12345',
            'mobile'                  => null,
            'fax'                     => null,
            'website'                 => 'https://acme.de',
            'active'                  => true,
            'blocked'                 => false,
            'insolvent'               => false,
            'vatRegistrationNumber'   => 'DE123456789',
            'currencyId'              => 'eur-id',
            'currencyName'            => 'EUR',
            'paymentTermId'           => 'pt-1',
            'deliveryTermId'          => 'dt-1',
            'responsibleUserId'       => 'user-1',
            'responsibleUserUsername' => 'mtosch',
            'salesChannel'            => 'ONLINE',
            'addresses'               => [['street1' => 'Hauptstraße 1']],
            'contacts'                => [],
            'tags'                    => [],
            'customAttributes'        => [],
        ];
    }

    public function test_creates_dto_from_array(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());

        self::assertSame('abc-123', $dto->id);
        self::assertSame('K-10042', $dto->customerNumber);
        self::assertSame('Acme GmbH', $dto->company);
        self::assertSame('info@acme.de', $dto->email);
        self::assertTrue($dto->active);
        self::assertFalse($dto->blocked);
        self::assertSame('EUR', $dto->currencyName);
        self::assertCount(1, $dto->addresses);
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
        $data['company']   = '';
        $data['firstName'] = 'Max';
        $data['lastName']  = 'Mustermann';

        $dto = CustomerDTO::fromArray($data);

        self::assertSame('Max Mustermann', $dto->getDisplayName());
    }

    public function test_get_created_at_returns_datetime(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());
        $dt  = $dto->getCreatedAt();

        self::assertNotNull($dt);
        // Epoch 1711400000000ms = 1711400000s
        self::assertSame(1711400000, $dt->getTimestamp());
    }

    public function test_get_last_modified_at_returns_datetime(): void
    {
        $dto = CustomerDTO::fromArray($this->sampleData());

        self::assertNotNull($dto->getLastModifiedAt());
        self::assertSame(1711450000, $dto->getLastModifiedAt()->getTimestamp());
    }

    public function test_to_array_round_trip(): void
    {
        $original = $this->sampleData();
        $dto      = CustomerDTO::fromArray($original);
        $array    = $dto->toArray();

        self::assertSame('abc-123', $array['id']);
        self::assertSame('K-10042', $array['customerNumber']);
        self::assertSame('Acme GmbH', $array['company']);
    }

    public function test_handles_missing_optional_fields_gracefully(): void
    {
        $dto = CustomerDTO::fromArray([
            'id'              => 'minimal-id',
            'customerNumber'  => 'K-1',
            'createdDate'     => 0,
            'lastModifiedDate' => 0,
        ]);

        self::assertSame('minimal-id', $dto->id);
        self::assertNull($dto->email);
        self::assertNull($dto->phone);
        self::assertSame([], $dto->addresses);
        self::assertFalse($dto->blocked);
    }
}
