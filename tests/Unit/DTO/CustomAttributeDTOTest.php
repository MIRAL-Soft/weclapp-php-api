<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use DateTimeImmutable;
use miralsoft\weclapp\api\DTO\CustomAttributeDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CustomAttributeDTO value readers and payload builders.
 */
final class CustomAttributeDTOTest extends TestCase
{
    // ── value() reader ──────────────────────────────────────────────────────────

    public function test_value_returns_string_value(): void
    {
        $dto = CustomAttributeDTO::fromArray([
            'attributeDefinitionId' => '998852',
            'stringValue'           => 'TICKET-4711',
        ]);

        self::assertSame('TICKET-4711', $dto->value());
        self::assertSame('TICKET-4711', $dto->stringValue);
    }

    public function test_value_returns_number_value(): void
    {
        $dto = CustomAttributeDTO::fromArray([
            'attributeDefinitionId' => 'x',
            'numberValue'           => '42.50',
        ]);

        self::assertSame('42.50', $dto->value());
    }

    public function test_value_falls_back_to_boolean_when_no_other_value_set(): void
    {
        $dto = CustomAttributeDTO::fromArray([
            'attributeDefinitionId' => 'x',
            'booleanValue'          => true,
        ]);

        self::assertTrue($dto->value());
    }

    // ── payload builders ────────────────────────────────────────────────────────

    public function test_string_builder(): void
    {
        self::assertSame(
            ['attributeDefinitionId' => 'd1', 'stringValue' => 'hello'],
            CustomAttributeDTO::string('d1', 'hello'),
        );
    }

    public function test_number_builder_casts_to_string(): void
    {
        self::assertSame(
            ['attributeDefinitionId' => 'd1', 'numberValue' => '42'],
            CustomAttributeDTO::number('d1', 42),
        );
        self::assertSame(
            ['attributeDefinitionId' => 'd1', 'numberValue' => null],
            CustomAttributeDTO::number('d1', null),
        );
    }

    public function test_boolean_builder(): void
    {
        self::assertSame(
            ['attributeDefinitionId' => 'd1', 'booleanValue' => true],
            CustomAttributeDTO::boolean('d1', true),
        );
    }

    public function test_date_builder_from_epoch_ms(): void
    {
        self::assertSame(
            ['attributeDefinitionId' => 'd1', 'dateValue' => 1779799373074],
            CustomAttributeDTO::date('d1', 1779799373074),
        );
    }

    public function test_date_builder_from_datetime_preserves_milliseconds(): void
    {
        $ms = 1779799373074;
        $dt = DateTimeImmutable::createFromFormat(
            'U.u',
            sprintf('%d.%03d', intdiv($ms, 1000), $ms % 1000),
        );

        $fragment = CustomAttributeDTO::date('d1', $dt);

        self::assertSame($ms, $fragment['dateValue']);
    }

    public function test_selection_builder(): void
    {
        self::assertSame(
            ['attributeDefinitionId' => 'd1', 'selectedValueId' => 'opt-7'],
            CustomAttributeDTO::selection('d1', 'opt-7'),
        );
    }
}
