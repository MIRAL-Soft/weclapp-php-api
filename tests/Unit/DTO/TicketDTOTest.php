<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\TicketDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TicketDTO.
 */
class TicketDTOTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'id'                    => 'ticket-123',
            'version'               => '2',
            'createdDate'           => 1711400000000,
            'lastModifiedDate'      => 1711450000000,
            'ticketNumber'          => 'TI-1042',
            'subject'               => 'Login does not work',
            'description'           => '<p>Unable to log in since update.</p>',
            'note'                  => null,
            'ticketStatusId'        => 'status-open',
            'ticketTypeId'          => 'type-bug',
            'ticketCategoryId'      => null,
            'ticketChannelId'       => 'channel-email',
            'ticketPriorityId'      => 'prio-high',
            'ticketServiceLevelAgreementId' => null,
            'assignedUserId'        => 'user-42',
            'assignedPoolingGroupId' => null,
            'responsibleUserId'     => 'user-1',
            'partyId'               => 'party-99',
            'contactId'             => null,
            'contractId'            => null,
            'salesOrderId'          => null,
            'legacyArticleId'       => null,
            'mail2TicketId'         => null,
            'firstName'             => 'Anna',
            'lastName'              => 'Musterfrau',
            'email'                 => 'anna@example.com',
            'ccEmailAddresses'      => null,
            'phoneNumber'           => null,
            'mobilePhoneNumber'     => null,
            'room'                  => null,
            'language'              => 'DE',
            'invoicingStatus'       => null,
            'performanceRecordedStatus' => null,
            'ticketRating'          => 'STARS_4',
            'ticketRatingComment'   => 'Good support!',
            'ticketRatingDate'      => 1711500000000,
            'publicPageUuid'        => null,
            'publicPageExpirationDate' => null,
            'finishedDate'          => null,
            'followUpDate'          => null,
            'solutionDueDate'       => 1711800000000,
            'billable'              => true,
            'billableStatus'        => false,
            'disableEmailTemplates' => false,
            'isTemplate'            => false,
            'legacyTimeAndMaterialTicket' => false,
            'resolvedYourIssue'     => false,
            'customAttributes'      => [],
            'entityReferences'      => [],
            'tags'                  => [],
            'watchers'              => [],
        ];
    }

    public function test_creates_from_array(): void
    {
        $dto = TicketDTO::fromArray($this->sampleData());

        self::assertSame('ticket-123', $dto->id);
        self::assertSame('TI-1042', $dto->ticketNumber);
        self::assertSame('Login does not work', $dto->subject);
        self::assertSame('user-42', $dto->assignedUserId);
        self::assertSame('party-99', $dto->partyId);
        self::assertSame('STARS_4', $dto->ticketRating);
        self::assertTrue($dto->billable);
        self::assertFalse($dto->billableStatus);
    }

    public function test_get_contact_display_name(): void
    {
        $dto = TicketDTO::fromArray($this->sampleData());

        self::assertSame('Anna Musterfrau', $dto->getContactDisplayName());
    }

    public function test_get_contact_display_name_with_only_first_name(): void
    {
        $data             = $this->sampleData();
        $data['lastName'] = null;

        $dto = TicketDTO::fromArray($data);

        self::assertSame('Anna', $dto->getContactDisplayName());
    }

    public function test_get_contact_display_name_empty_when_both_null(): void
    {
        $data              = $this->sampleData();
        $data['firstName'] = null;
        $data['lastName']  = null;

        $dto = TicketDTO::fromArray($data);

        self::assertSame('', $dto->getContactDisplayName());
    }

    public function test_get_rating_stars_returns_correct_int(): void
    {
        $dto = TicketDTO::fromArray($this->sampleData());

        self::assertSame(4, $dto->getRatingStars());
    }

    public function test_get_rating_stars_returns_all_values(): void
    {
        foreach (['STARS_1' => 1, 'STARS_2' => 2, 'STARS_3' => 3, 'STARS_4' => 4, 'STARS_5' => 5] as $enum => $expected) {
            $data                 = $this->sampleData();
            $data['ticketRating'] = $enum;

            self::assertSame($expected, TicketDTO::fromArray($data)->getRatingStars());
        }
    }

    public function test_get_rating_stars_returns_null_when_not_set(): void
    {
        $data                 = $this->sampleData();
        $data['ticketRating'] = null;

        $dto = TicketDTO::fromArray($data);

        self::assertNull($dto->getRatingStars());
    }

    public function test_is_billed_returns_false_when_billable_but_not_confirmed(): void
    {
        $dto = TicketDTO::fromArray($this->sampleData());

        // billable = true, billableStatus = false → not yet billed
        self::assertFalse($dto->isBilled());
    }

    public function test_is_billed_returns_true_when_both_flags_true(): void
    {
        $data                   = $this->sampleData();
        $data['billableStatus'] = true;

        $dto = TicketDTO::fromArray($data);

        self::assertTrue($dto->isBilled());
    }

    public function test_get_solution_due_date_returns_datetime(): void
    {
        $dto = TicketDTO::fromArray($this->sampleData());
        $dt  = $dto->getSolutionDueDate();

        self::assertNotNull($dt);
        self::assertSame(1711800000, $dt->getTimestamp());
    }

    public function test_get_finished_date_returns_null_when_not_set(): void
    {
        $dto = TicketDTO::fromArray($this->sampleData());

        self::assertNull($dto->getFinishedDate());
    }

    public function test_get_created_at_returns_datetime(): void
    {
        $dto = TicketDTO::fromArray($this->sampleData());

        self::assertSame(1711400000, $dto->getCreatedAt()->getTimestamp());
    }

    public function test_handles_missing_optional_fields(): void
    {
        $dto = TicketDTO::fromArray([
            'id'              => 'min-id',
            'version'         => '1',
            'createdDate'     => 0,
            'lastModifiedDate' => 0,
            'ticketNumber'    => 'TI-1',
        ]);

        self::assertNull($dto->subject);
        self::assertNull($dto->partyId);
        self::assertNull($dto->ticketRating);
        self::assertNull($dto->getRatingStars());
        self::assertFalse($dto->billable);
        self::assertSame([], $dto->customAttributes);
    }
}
