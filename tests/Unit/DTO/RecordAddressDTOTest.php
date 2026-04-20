<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\RecordAddressDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RecordAddressDTO.
 *
 * Maps to the recordAddress schema (18 fields, no identity, has middleName).
 */
class RecordAddressDTOTest extends TestCase
{
    public function test_creates_from_full_data(): void
    {
        $dto = RecordAddressDTO::fromArray([
            'city'                => 'Berlin',
            'company'             => 'Acme GmbH',
            'company2'            => 'Abt. IT',
            'countryCode'         => 'DE',
            'firstName'           => 'Max',
            'globalLocationNumber' => '4012345000009',
            'lastName'            => 'Mustermann',
            'middleName'          => 'Werner',
            'phoneNumber'         => '+49 30 12345',
            'postOfficeBoxCity'   => null,
            'postOfficeBoxNumber' => null,
            'postOfficeBoxZipCode' => null,
            'salutation'          => 'MR',
            'state'               => 'Berlin',
            'street1'             => 'Hauptstraße 1',
            'street2'             => 'EG',
            'titleId'             => null,
            'zipcode'             => '10115',
        ]);

        self::assertSame('Berlin', $dto->city);
        self::assertSame('Acme GmbH', $dto->company);
        self::assertSame('Werner', $dto->middleName);
        self::assertSame('Hauptstraße 1', $dto->street1);
        self::assertSame('DE', $dto->countryCode);
    }

    public function test_all_fields_are_null_when_missing(): void
    {
        $dto = RecordAddressDTO::fromArray([]);

        self::assertNull($dto->city);
        self::assertNull($dto->company);
        self::assertNull($dto->middleName);
        self::assertNull($dto->street1);
        self::assertNull($dto->countryCode);
    }

    public function test_get_display_line_with_company(): void
    {
        $dto = RecordAddressDTO::fromArray([
            'company'     => 'Acme GmbH',
            'street1'     => 'Hauptstraße 1',
            'zipcode'     => '10115',
            'city'        => 'Berlin',
            'countryCode' => 'DE',
        ]);

        self::assertSame('Acme GmbH, Hauptstraße 1, 10115 Berlin, DE', $dto->getDisplayLine());
    }

    public function test_get_display_line_falls_back_to_person_name(): void
    {
        $dto = RecordAddressDTO::fromArray([
            'firstName'   => 'Max',
            'middleName'  => 'Werner',
            'lastName'    => 'Mustermann',
            'street1'     => 'Gartenweg 5',
            'zipcode'     => '80331',
            'city'        => 'München',
            'countryCode' => 'DE',
        ]);

        self::assertSame('Max Werner Mustermann, Gartenweg 5, 80331 München, DE', $dto->getDisplayLine());
    }

    public function test_get_display_line_is_empty_for_empty_object(): void
    {
        $dto = RecordAddressDTO::fromArray([]);

        self::assertSame('', $dto->getDisplayLine());
    }

    public function test_has_no_identity_fields(): void
    {
        // recordAddress has no id/version/createdDate/lastModifiedDate
        $dto = RecordAddressDTO::fromArray(['city' => 'Berlin']);

        self::assertFalse(property_exists($dto, 'id'));
        self::assertFalse(property_exists($dto, 'version'));
        self::assertFalse(property_exists($dto, 'createdDate'));
        self::assertFalse(property_exists($dto, 'lastModifiedDate'));
    }
}
