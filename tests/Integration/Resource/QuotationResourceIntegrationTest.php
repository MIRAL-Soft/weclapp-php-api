<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\QuotationDTO;
use miralsoft\weclapp\api\Enum\QuotationStatus;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for QuotationResource (/api/v2/quotation).
 * All tests are read-only.
 */
class QuotationResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_quotation_dtos(): void
    {
        $result = $this->client()->quotations()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertContainsOnlyInstancesOf(QuotationDTO::class, $result->items);
    }

    public function test_first_quotation_has_required_fields(): void
    {
        $result = $this->client()->quotations()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No quotations found in this tenant.');
        }

        $quotation = $result->items[0];

        self::assertNotEmpty($quotation->id);
        self::assertNotEmpty($quotation->quotationNumber);
        self::assertIsInt($quotation->createdDate);
    }

    public function test_quotation_status_maps_to_known_enum_value(): void
    {
        $result = $this->client()->quotations()->list(
            QueryBuilder::new()->pageSize(10),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No quotations found in this tenant.');
        }

        foreach ($result->items as $quotation) {
            if ($quotation->status === null) {
                continue;
            }

            $status = QuotationStatus::tryFrom($quotation->status);
            self::assertNotNull(
                $status,
                "Unknown quotation status '{$quotation->status}' on quotation {$quotation->quotationNumber} — " .
                'update QuotationStatus enum if weclapp added a new value.',
            );
        }
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->quotations()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No quotations found in this tenant.');
        }

        $id        = $result->items[0]->id;
        $quotation = $this->client()->quotations()->find($id);

        self::assertInstanceOf(QuotationDTO::class, $quotation);
        self::assertSame($id, $quotation->id);
    }

    public function test_count_returns_non_negative_integer(): void
    {
        $count = $this->client()->quotations()->count();

        self::assertIsInt($count);
        self::assertGreaterThanOrEqual(0, $count);
    }

    public function test_find_by_customer_returns_quotation_dtos(): void
    {
        $result = $this->client()->quotations()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No quotations found in this tenant.');
        }

        $customerId = $result->items[0]->customerId;

        if (empty($customerId)) {
            $this->markTestSkipped('First quotation has no customerId.');
        }

        $quotations = $this->client()->quotations()->findByCustomer($customerId);

        self::assertContainsOnlyInstancesOf(QuotationDTO::class, $quotations);
        self::assertGreaterThan(0, count($quotations));
    }

    public function test_modified_since_returns_valid_dtos(): void
    {
        $result = $this->client()->quotations()->list(
            QueryBuilder::new()
                ->modifiedSince(new \DateTime('-1 year'))
                ->pageSize(5),
        );

        self::assertContainsOnlyInstancesOf(QuotationDTO::class, $result->items);
    }

    public function test_find_by_quotation_number_returns_same_record(): void
    {
        $result = $this->client()->quotations()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No quotations found in this tenant.');
        }

        $quotationNumber = $result->items[0]->quotationNumber;
        $quotation       = $this->client()->quotations()->findByQuotationNumber($quotationNumber);

        self::assertInstanceOf(QuotationDTO::class, $quotation);
        self::assertSame($quotationNumber, $quotation->quotationNumber);
    }
}
