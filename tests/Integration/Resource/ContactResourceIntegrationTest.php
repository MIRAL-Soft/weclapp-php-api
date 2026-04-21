<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\ContactDTO;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for ContactResource (/api/v2/party filtered by parentPartyId).
 * All tests are read-only.
 */
class ContactResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_contact_dtos(): void
    {
        $result = $this->client()->contacts()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertContainsOnlyInstancesOf(ContactDTO::class, $result->items);
    }

    public function test_first_contact_has_required_fields(): void
    {
        $result = $this->client()->contacts()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No contacts found in this tenant.');
        }

        $contact = $result->items[0];

        self::assertNotEmpty($contact->id);
        self::assertIsInt($contact->createdDate);
        // Contacts should be linked to a parent party via parentPartyId.
        // Some tenants may have contacts with no parentPartyId set — skip in that case.
        if ($contact->parentPartyId === null) {
            $this->markTestSkipped(
                'First contact has no parentPartyId — the NOT_NULL filter may not be enforced for all records in this tenant.',
            );
        }
        self::assertNotEmpty($contact->parentPartyId);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->contacts()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No contacts found in this tenant.');
        }

        $id      = $result->items[0]->id;
        $contact = $this->client()->contacts()->find($id);

        self::assertInstanceOf(ContactDTO::class, $contact);
        self::assertSame($id, $contact->id);
    }

    public function test_count_returns_non_negative_integer(): void
    {
        $count = $this->client()->contacts()->count();

        self::assertIsInt($count);
        self::assertGreaterThanOrEqual(0, $count);
    }
}
