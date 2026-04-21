<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\PartyDTO;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for PartyResource (/api/v2/party).
 *
 * The party endpoint is the single source for all party types
 * (customers, suppliers, contacts). All tests are read-only.
 */
class PartyResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_party_dtos(): void
    {
        $result = $this->client()->parties()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertNotEmpty($result->items, 'Expected at least one party in this tenant.');
        self::assertContainsOnlyInstancesOf(PartyDTO::class, $result->items);
    }

    public function test_first_party_has_required_fields(): void
    {
        $result = $this->client()->parties()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No parties found in this tenant.');
        }

        $party = $result->items[0];

        self::assertNotEmpty($party->id);
        self::assertIsInt($party->createdDate);
        self::assertIsInt($party->lastModifiedDate);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->parties()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No parties found in this tenant.');
        }

        $id    = $result->items[0]->id;
        $party = $this->client()->parties()->find($id);

        self::assertInstanceOf(PartyDTO::class, $party);
        self::assertSame($id, $party->id);
    }

    public function test_count_returns_positive_integer(): void
    {
        $count = $this->client()->parties()->count();

        self::assertIsInt($count);
        self::assertGreaterThan(0, $count);
    }
}
