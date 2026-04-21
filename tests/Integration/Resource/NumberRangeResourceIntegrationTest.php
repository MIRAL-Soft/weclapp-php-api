<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\NumberRangeDTO;
use miralsoft\weclapp\api\DTO\NumberRangeValueDTO;
use miralsoft\weclapp\api\Enum\NumberRangeType;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for NumberRangeResource and NumberRangeValueResource.
 *
 * All tests are read-only — no data is created or modified.
 */
class NumberRangeResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_at_least_one_range(): void
    {
        $result = $this->client()->numberRanges()->list();

        self::assertContainsOnlyInstancesOf(NumberRangeDTO::class, $result->items);
        self::assertGreaterThan(
            0,
            count($result->items),
            'Every weclapp tenant must have at least one number range configured.',
        );
    }

    public function test_each_range_has_a_known_type(): void
    {
        $result = $this->client()->numberRanges()->list();

        foreach ($result->items as $range) {
            self::assertNotEmpty($range->type);
            self::assertNotEmpty($range->id);

            // getType() returns null for types not yet in the enum — that's a hint to update it
            $typed = $range->getType();
            if ($typed === null) {
                $this->addWarning(
                    "Unknown numberRangeType '{$range->type}' — consider adding it to NumberRangeType enum.",
                );
            }
        }
    }

    public function test_find_by_type_with_sales_invoice(): void
    {
        $range = $this->client()->numberRanges()->findByType(NumberRangeType::SalesInvoice);

        if ($range === null) {
            $this->markTestSkipped('SALES_INVOICE number range is not configured in this tenant.');
        }

        self::assertInstanceOf(NumberRangeDTO::class, $range);
        self::assertSame('SALES_INVOICE', $range->type);
        self::assertNotEmpty($range->id);
    }

    public function test_number_range_values_exist_for_configured_ranges(): void
    {
        $result = $this->client()->numberRanges()->list();

        if (empty($result->items)) {
            $this->markTestSkipped('No number ranges configured.');
        }

        // Check the first range has at least one value
        $firstRange = $result->items[0];
        $values     = $this->client()->numberRangeValues()->findByNumberRange($firstRange->id);

        self::assertContainsOnlyInstancesOf(NumberRangeValueDTO::class, $values);
        self::assertGreaterThan(
            0,
            count($values),
            "Number range '{$firstRange->type}' should have at least one value entry.",
        );
    }

    public function test_number_range_value_has_required_fields(): void
    {
        $result = $this->client()->numberRanges()->list();

        if (empty($result->items)) {
            $this->markTestSkipped('No number ranges configured.');
        }

        $values = $this->client()->numberRangeValues()->findByNumberRange($result->items[0]->id);

        if (empty($values)) {
            $this->markTestSkipped('No number range values found for first range.');
        }

        $value = $values[0];

        self::assertNotEmpty($value->id);
        self::assertNotEmpty($value->numberRangeId);
        self::assertIsInt($value->interval);
        self::assertIsInt($value->lastValue);
    }

    public function test_proforma_invoice_prefix_returns_string_or_skips(): void
    {
        $prefix = $this->client()->numberRanges()->getProformaInvoicePrefix();

        if ($prefix === null) {
            $this->markTestSkipped('PROFORMA_INVOICE number range is not configured in this tenant.');
        }

        self::assertIsString($prefix);
        self::assertGreaterThan(0, strlen($prefix), 'Proforma prefix must not be empty.');
    }

    public function test_active_value_is_detected_correctly(): void
    {
        $result = $this->client()->numberRanges()->list();

        if (empty($result->items)) {
            $this->markTestSkipped('No number ranges configured.');
        }

        foreach ($result->items as $range) {
            $values = $this->client()->numberRangeValues()->findByNumberRange($range->id);

            foreach ($values as $value) {
                // isCurrentlyActive() must not throw — it is a pure boolean computation
                $active = $value->isCurrentlyActive();
                self::assertIsBool($active);
            }

            // Only check the first range to keep the test fast
            break;
        }
    }
}
