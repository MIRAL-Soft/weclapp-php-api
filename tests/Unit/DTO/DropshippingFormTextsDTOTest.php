<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\DropshippingFormTextsDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DropshippingFormTextsDTO.
 *
 * Maps to the dropshippingDeliveryNoteFormTextBlockData schema (3 optional string fields).
 */
class DropshippingFormTextsDTOTest extends TestCase
{
    public function test_creates_from_full_data(): void
    {
        $dto = DropshippingFormTextsDTO::fromArray([
            'recordComment'  => '<p>Please deliver carefully.</p>',
            'recordFreeText' => '<p>Thank you for your order.</p>',
            'recordOpening'  => '<p>Dear recipient,</p>',
        ]);

        self::assertSame('<p>Please deliver carefully.</p>', $dto->recordComment);
        self::assertSame('<p>Thank you for your order.</p>', $dto->recordFreeText);
        self::assertSame('<p>Dear recipient,</p>', $dto->recordOpening);
    }

    public function test_all_fields_null_when_missing(): void
    {
        $dto = DropshippingFormTextsDTO::fromArray([]);

        self::assertNull($dto->recordComment);
        self::assertNull($dto->recordFreeText);
        self::assertNull($dto->recordOpening);
    }

    public function test_is_empty_returns_true_when_all_null(): void
    {
        $dto = DropshippingFormTextsDTO::fromArray([]);

        self::assertTrue($dto->isEmpty());
    }

    public function test_is_empty_returns_false_when_any_field_set(): void
    {
        $dto = DropshippingFormTextsDTO::fromArray(['recordComment' => 'Handle with care.']);

        self::assertFalse($dto->isEmpty());
    }
}
