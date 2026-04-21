<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\TicketDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for TicketResource (/api/v2/ticket).
 * All tests are read-only.
 *
 * All tests are automatically skipped if the API token lacks permission
 * to access the ticket endpoint (HTTP 403).
 */
class TicketResourceIntegrationTest extends IntegrationTestCase
{
    /**
     * Skip all tests in this class if the API token has no access to the
     * ticket endpoint (HTTP 403). This avoids confusing errors when the
     * token is scoped to a subset of weclapp resources.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->client()->tickets()->list(QueryBuilder::new()->pageSize(1));
        } catch (WeclappApiException $e) {
            if (str_contains($e->getMessage(), 'HTTP 403')) {
                $this->markTestSkipped(
                    'ticket endpoint returned HTTP 403 — API token lacks permission. ' .
                    'Grant the "Tickets" read right in weclapp → Settings → Users.',
                );
            }
            throw $e;
        }
    }

    public function test_list_returns_ticket_dtos(): void
    {
        $result = $this->client()->tickets()->list(
            QueryBuilder::new()->pageSize(5),
        );

        self::assertIsArray($result->items);
        self::assertContainsOnlyInstancesOf(TicketDTO::class, $result->items);
    }

    public function test_first_ticket_has_required_fields(): void
    {
        $result = $this->client()->tickets()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No tickets found in this tenant.');
        }

        $ticket = $result->items[0];

        self::assertNotEmpty($ticket->id);
        self::assertNotEmpty($ticket->ticketNumber);
        self::assertIsInt($ticket->createdDate);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->tickets()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No tickets found in this tenant.');
        }

        $id     = $result->items[0]->id;
        $ticket = $this->client()->tickets()->find($id);

        self::assertInstanceOf(TicketDTO::class, $ticket);
        self::assertSame($id, $ticket->id);
    }

    public function test_count_returns_non_negative_integer(): void
    {
        $count = $this->client()->tickets()->count();

        self::assertIsInt($count);
        self::assertGreaterThanOrEqual(0, $count);
    }

    public function test_find_by_party_returns_ticket_dtos(): void
    {
        $result = $this->client()->tickets()->list(
            QueryBuilder::new()->pageSize(5),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No tickets found in this tenant.');
        }

        // Find the first ticket that is linked to a party
        $partyId = null;
        foreach ($result->items as $ticket) {
            if ($ticket->partyId !== null) {
                $partyId = $ticket->partyId;
                break;
            }
        }

        if ($partyId === null) {
            $this->markTestSkipped('No tickets with partyId found in first page.');
        }

        $tickets = $this->client()->tickets()->findByParty($partyId);

        self::assertContainsOnlyInstancesOf(TicketDTO::class, $tickets);
        self::assertGreaterThan(0, count($tickets));
    }

    public function test_find_by_status_returns_ticket_dtos(): void
    {
        $result = $this->client()->tickets()->list(
            QueryBuilder::new()->pageSize(5),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No tickets found in this tenant.');
        }

        // Find the first ticket that has a status assigned
        $statusId = null;
        foreach ($result->items as $ticket) {
            if ($ticket->ticketStatusId !== null) {
                $statusId = $ticket->ticketStatusId;
                break;
            }
        }

        if ($statusId === null) {
            $this->markTestSkipped('No tickets with ticketStatusId found in first page.');
        }

        $tickets = $this->client()->tickets()->findByStatus($statusId);

        self::assertContainsOnlyInstancesOf(TicketDTO::class, $tickets);
        self::assertGreaterThan(0, count($tickets));
    }

    public function test_modified_since_returns_valid_dtos(): void
    {
        $result = $this->client()->tickets()->list(
            QueryBuilder::new()
                ->modifiedSince(new \DateTime('-1 year'))
                ->pageSize(5),
        );

        self::assertContainsOnlyInstancesOf(TicketDTO::class, $result->items);
    }
}
