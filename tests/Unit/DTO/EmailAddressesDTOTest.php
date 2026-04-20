<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\EmailAddressesDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EmailAddressesDTO.
 *
 * The emailAddresses schema defines bccAddresses / ccAddresses / toAddresses
 * as plain strings (comma-separated), not arrays.
 */
class EmailAddressesDTOTest extends TestCase
{
    public function test_creates_from_array_with_string_fields(): void
    {
        $dto = EmailAddressesDTO::fromArray([
            'toAddresses'  => 'max@example.com,anna@example.com',
            'ccAddresses'  => 'info@example.com',
            'bccAddresses' => null,
        ]);

        self::assertSame('max@example.com,anna@example.com', $dto->toAddresses);
        self::assertSame('info@example.com', $dto->ccAddresses);
        self::assertNull($dto->bccAddresses);
    }

    public function test_get_all_addresses_parses_comma_separated(): void
    {
        $dto = EmailAddressesDTO::fromArray([
            'toAddresses'  => 'max@example.com , anna@example.com',
            'ccAddresses'  => 'info@example.com',
            'bccAddresses' => null,
        ]);

        $all = $dto->getAllAddresses();

        self::assertCount(3, $all);
        self::assertContains('max@example.com', $all);
        self::assertContains('anna@example.com', $all);
        self::assertContains('info@example.com', $all);
    }

    public function test_get_all_addresses_deduplicates(): void
    {
        $dto = EmailAddressesDTO::fromArray([
            'toAddresses'  => 'shared@example.com',
            'ccAddresses'  => 'shared@example.com',
            'bccAddresses' => 'shared@example.com',
        ]);

        self::assertCount(1, $dto->getAllAddresses());
        self::assertSame(['shared@example.com'], $dto->getAllAddresses());
    }

    public function test_get_all_addresses_returns_empty_for_all_null(): void
    {
        $dto = EmailAddressesDTO::fromArray([]);

        self::assertSame([], $dto->getAllAddresses());
    }

    public function test_is_empty_returns_true_when_all_null(): void
    {
        $dto = EmailAddressesDTO::fromArray([]);

        self::assertTrue($dto->isEmpty());
    }

    public function test_is_empty_returns_false_when_any_field_set(): void
    {
        $dto = EmailAddressesDTO::fromArray(['toAddresses' => 'someone@example.com']);

        self::assertFalse($dto->isEmpty());
    }

    public function test_is_empty_returns_true_for_empty_string(): void
    {
        $dto = EmailAddressesDTO::fromArray([
            'toAddresses'  => '',
            'ccAddresses'  => '',
            'bccAddresses' => '',
        ]);

        self::assertTrue($dto->isEmpty());
    }
}
