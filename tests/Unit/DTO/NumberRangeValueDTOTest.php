<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\NumberRangeValueDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NumberRangeValueDTO.
 */
class NumberRangeValueDTOTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'id'                    => 'nrv-1',
            'version'               => '1',
            'createdDate'           => 1711400000000,
            'lastModifiedDate'      => 1711450000000,
            'numberRangeId'         => 'nr-proforma',
            'interval'              => 1,
            'lastValue'             => 41,
            'length'                => 4,
            'prefix'                => 'PR-',
            'suffix'                => null,
            'validFromDate'         => null,
            'validToDate'           => null,
            'salesInvoiceTypes'     => [],
            'creditNoteInvoiceTypes' => [],
            'salesChannels'         => [],
            'articleCategories'     => [],
        ];
    }

    public function test_creates_from_array(): void
    {
        $dto = NumberRangeValueDTO::fromArray($this->sampleData());

        self::assertSame('nrv-1', $dto->id);
        self::assertSame('nr-proforma', $dto->numberRangeId);
        self::assertSame('PR-', $dto->prefix);
        self::assertNull($dto->suffix);
        self::assertSame(1, $dto->interval);
        self::assertSame(41, $dto->lastValue);
        self::assertSame(4, $dto->length);
    }

    public function test_sales_invoice_types_keep_zero_string_but_drop_empty(): void
    {
        $data = $this->sampleData();
        $data['salesInvoiceTypes']      = ['STANDARD_INVOICE', '0', ''];
        $data['creditNoteInvoiceTypes'] = ['', 'CREDIT_NOTE', '0'];

        $dto = NumberRangeValueDTO::fromArray($data);

        // "0" is a legitimate string value and must survive; only "" is dropped.
        self::assertSame(['STANDARD_INVOICE', '0'], $dto->salesInvoiceTypes);
        self::assertSame(['CREDIT_NOTE', '0'], $dto->creditNoteInvoiceTypes);
    }

    public function test_prefix_and_suffix_are_null_when_absent(): void
    {
        $data = $this->sampleData();
        unset($data['prefix'], $data['suffix']);

        $dto = NumberRangeValueDTO::fromArray($data);

        self::assertNull($dto->prefix);
        self::assertNull($dto->suffix);
    }

    public function test_is_currently_active_when_no_validity_dates(): void
    {
        $dto = NumberRangeValueDTO::fromArray($this->sampleData());

        self::assertTrue($dto->isCurrentlyActive());
    }

    public function test_is_currently_active_returns_false_for_expired_range(): void
    {
        $data                  = $this->sampleData();
        $data['validFromDate'] = 1000000000000; // year 2001
        $data['validToDate']   = 1100000000000; // year 2004 — expired

        $dto = NumberRangeValueDTO::fromArray($data);

        self::assertFalse($dto->isCurrentlyActive());
    }

    public function test_is_currently_active_returns_false_for_future_range(): void
    {
        $data                  = $this->sampleData();
        $data['validFromDate'] = (int) (microtime(true) * 1000) + 86_400_000; // tomorrow

        $dto = NumberRangeValueDTO::fromArray($data);

        self::assertFalse($dto->isCurrentlyActive());
    }

    public function test_get_valid_from_returns_datetime(): void
    {
        $data                  = $this->sampleData();
        $data['validFromDate'] = 1711400000000;

        $dto = NumberRangeValueDTO::fromArray($data);

        self::assertSame(1711400000, $dto->getValidFrom()->getTimestamp());
    }

    public function test_get_valid_to_returns_null_when_absent(): void
    {
        $dto = NumberRangeValueDTO::fromArray($this->sampleData());

        self::assertNull($dto->getValidTo());
    }

    public function test_format_next_number_with_padding(): void
    {
        // lastValue=41, interval=1 → next=42, padded to 4 digits → "PR-0042"
        $dto = NumberRangeValueDTO::fromArray($this->sampleData());

        self::assertSame('PR-0042', $dto->formatNextNumber());
    }

    public function test_format_next_number_without_padding(): void
    {
        $data           = $this->sampleData();
        $data['prefix'] = 'RE-';
        $data['length'] = null;
        $data['lastValue'] = 10042;

        $dto = NumberRangeValueDTO::fromArray($data);

        self::assertSame('RE-10043', $dto->formatNextNumber());
    }

    public function test_format_next_number_with_suffix(): void
    {
        $data           = $this->sampleData();
        $data['prefix'] = 'INV-';
        $data['suffix'] = '-DE';
        $data['length'] = null;
        $data['lastValue'] = 99;

        $dto = NumberRangeValueDTO::fromArray($data);

        self::assertSame('INV-100-DE', $dto->formatNextNumber());
    }

    public function test_sales_invoice_types_array_is_preserved(): void
    {
        $data                        = $this->sampleData();
        $data['salesInvoiceTypes']   = ['STANDARD_INVOICE', 'FINAL_INVOICE'];
        $data['creditNoteInvoiceTypes'] = ['CREDIT_NOTE'];

        $dto = NumberRangeValueDTO::fromArray($data);

        self::assertSame(['STANDARD_INVOICE', 'FINAL_INVOICE'], $dto->salesInvoiceTypes);
        self::assertSame(['CREDIT_NOTE'], $dto->creditNoteInvoiceTypes);
    }

    public function test_get_created_at_returns_datetime(): void
    {
        $dto = NumberRangeValueDTO::fromArray($this->sampleData());

        self::assertSame(1711400000, $dto->getCreatedAt()->getTimestamp());
    }
}
