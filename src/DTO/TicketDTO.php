<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Support Ticket from the weclapp API.
 *
 * Maps to the ticket schema. Tickets track customer support requests,
 * complaints or service work. They can be linked to parties, contacts,
 * contracts and sales orders.
 *
 * @see \miralsoft\weclapp\api\Resource\TicketResource
 */
final class TicketDTO extends AbstractDTO
{
    /**
     * @param string      $id                             Internal weclapp UUID (readOnly).
     * @param string      $version                        Optimistic locking version string (readOnly).
     * @param int         $createdDate                    Creation timestamp in epoch milliseconds (readOnly).
     * @param int         $lastModifiedDate               Last modification timestamp in epoch milliseconds (readOnly).
     * @param string      $ticketNumber                   Human-readable ticket number (e.g. "TI-1042", readOnly).
     * @param string|null $subject                        Ticket subject / title.
     * @param string|null $description                    Ticket description (HTML).
     * @param string|null $note                           Internal note (HTML).
     * @param string|null $ticketStatusId                 ID of the current ticket status.
     * @param string|null $ticketTypeId                   ID of the ticket type.
     * @param string|null $ticketCategoryId               ID of the ticket category.
     * @param string|null $ticketChannelId                ID of the source channel (e.g. email, phone).
     * @param string|null $ticketPriorityId               ID of the ticket priority.
     * @param string|null $ticketServiceLevelAgreementId  ID of the associated SLA.
     * @param string|null $assignedUserId                 ID of the assigned staff user.
     * @param string|null $assignedPoolingGroupId         ID of the assigned pooling group.
     * @param string|null $responsibleUserId              ID of the responsible user.
     * @param string|null $partyId                        ID of the linked customer/contact/supplier party.
     * @param string|null $contactId                      ID of the specific contact person.
     * @param string|null $contractId                     ID of the linked contract.
     * @param string|null $salesOrderId                   ID of the linked sales order.
     * @param string|null $legacyArticleId                ID of the legacy article (time & material).
     * @param string|null $mail2TicketId                  ID of the mail2ticket configuration.
     * @param string|null $firstName                      Contact first name (denormalized from contact).
     * @param string|null $lastName                       Contact last name (denormalized from contact).
     * @param string|null $email                          Contact e-mail address.
     * @param string|null $ccEmailAddresses               Comma-separated CC e-mail addresses.
     * @param string|null $phoneNumber                    Contact phone number.
     * @param string|null $mobilePhoneNumber              Contact mobile phone number.
     * @param string|null $room                           Room / location reference.
     * @param string|null $language                       Language code for this ticket.
     * @param string|null $invoicingStatus                Invoicing status (enum: billableInvoiceStatus).
     * @param string|null $performanceRecordedStatus      Performance recording status (enum: performanceRecordedStatus).
     * @param string|null $ticketRating                   Customer rating (enum: STARS_1 to STARS_5).
     * @param string|null $ticketRatingComment            Customer rating comment.
     * @param int|null    $ticketRatingDate               Date of customer rating in epoch milliseconds.
     * @param string|null $publicPageUuid                 UUID of the public status page.
     * @param int|null    $publicPageExpirationDate        Expiration date of the public status page in epoch milliseconds.
     * @param int|null    $finishedDate                   Date the ticket was finished in epoch milliseconds.
     * @param int|null    $followUpDate                   Follow-up date in epoch milliseconds.
     * @param int|null    $solutionDueDate                Solution due date (SLA deadline) in epoch milliseconds.
     * @param bool        $billable                       True if this ticket is billable.
     * @param bool        $billableStatus                 True if billing has been confirmed.
     * @param bool        $disableEmailTemplates          True if automatic email templates are disabled.
     * @param bool        $isTemplate                     True if this ticket is a template.
     * @param bool        $legacyTimeAndMaterialTicket    True if this is a legacy time & material ticket.
     * @param bool        $resolvedYourIssue              True if the customer confirmed the issue is resolved.
     * @param list<CustomAttributeDTO> $customAttributes  Custom attribute values.
     * @param list<array>              $entityReferences  Linked entity references (raw {entityId, entityName} objects).
     * @param list<array>              $tags              List of tag objects.
     * @param list<array>              $watchers          Watching user references (raw {id} objects).
     */
    public function __construct(
        // Identity
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,

        // Core
        public readonly string  $ticketNumber,
        public readonly ?string $subject,
        public readonly ?string $description,
        public readonly ?string $note,

        // Status / classification
        public readonly ?string $ticketStatusId,
        public readonly ?string $ticketTypeId,
        public readonly ?string $ticketCategoryId,
        public readonly ?string $ticketChannelId,
        public readonly ?string $ticketPriorityId,
        public readonly ?string $ticketServiceLevelAgreementId,

        // Assignment
        public readonly ?string $assignedUserId,
        public readonly ?string $assignedPoolingGroupId,
        public readonly ?string $responsibleUserId,

        // Linked entities
        public readonly ?string $partyId,
        public readonly ?string $contactId,
        public readonly ?string $contractId,
        public readonly ?string $salesOrderId,
        public readonly ?string $legacyArticleId,
        public readonly ?string $mail2TicketId,

        // Contact info (denormalized)
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly ?string $ccEmailAddresses,
        public readonly ?string $phoneNumber,
        public readonly ?string $mobilePhoneNumber,
        public readonly ?string $room,
        public readonly ?string $language,

        // Billing / performance
        public readonly ?string $invoicingStatus,
        public readonly ?string $performanceRecordedStatus,

        // Rating
        public readonly ?string $ticketRating,
        public readonly ?string $ticketRatingComment,
        public readonly ?int    $ticketRatingDate,

        // Public page
        public readonly ?string $publicPageUuid,
        public readonly ?int    $publicPageExpirationDate,

        // Dates
        public readonly ?int    $finishedDate,
        public readonly ?int    $followUpDate,
        public readonly ?int    $solutionDueDate,

        // Flags
        public readonly bool    $billable,
        public readonly bool    $billableStatus,
        public readonly bool    $disableEmailTemplates,
        public readonly bool    $isTemplate,
        public readonly bool    $legacyTimeAndMaterialTicket,
        public readonly bool    $resolvedYourIssue,

        // Nested arrays
        public readonly array   $customAttributes,
        public readonly array   $entityReferences,
        public readonly array   $tags,
        public readonly array   $watchers,
    ) {}

    /**
     * Create a TicketDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                            self::str($data, 'id'),
            version:                       self::str($data, 'version'),
            createdDate:                   self::int($data, 'createdDate'),
            lastModifiedDate:              self::int($data, 'lastModifiedDate'),

            ticketNumber:                  self::str($data, 'ticketNumber'),
            subject:                       self::strOrNull($data, 'subject'),
            description:                   self::strOrNull($data, 'description'),
            note:                          self::strOrNull($data, 'note'),

            ticketStatusId:                self::strOrNull($data, 'ticketStatusId'),
            ticketTypeId:                  self::strOrNull($data, 'ticketTypeId'),
            ticketCategoryId:              self::strOrNull($data, 'ticketCategoryId'),
            ticketChannelId:               self::strOrNull($data, 'ticketChannelId'),
            ticketPriorityId:              self::strOrNull($data, 'ticketPriorityId'),
            ticketServiceLevelAgreementId: self::strOrNull($data, 'ticketServiceLevelAgreementId'),

            assignedUserId:                self::strOrNull($data, 'assignedUserId'),
            assignedPoolingGroupId:        self::strOrNull($data, 'assignedPoolingGroupId'),
            responsibleUserId:             self::strOrNull($data, 'responsibleUserId'),

            partyId:                       self::strOrNull($data, 'partyId'),
            contactId:                     self::strOrNull($data, 'contactId'),
            contractId:                    self::strOrNull($data, 'contractId'),
            salesOrderId:                  self::strOrNull($data, 'salesOrderId'),
            legacyArticleId:               self::strOrNull($data, 'legacyArticleId'),
            mail2TicketId:                 self::strOrNull($data, 'mail2TicketId'),

            firstName:                     self::strOrNull($data, 'firstName'),
            lastName:                      self::strOrNull($data, 'lastName'),
            email:                         self::strOrNull($data, 'email'),
            ccEmailAddresses:              self::strOrNull($data, 'ccEmailAddresses'),
            phoneNumber:                   self::strOrNull($data, 'phoneNumber'),
            mobilePhoneNumber:             self::strOrNull($data, 'mobilePhoneNumber'),
            room:                          self::strOrNull($data, 'room'),
            language:                      self::strOrNull($data, 'language'),

            invoicingStatus:               self::strOrNull($data, 'invoicingStatus'),
            performanceRecordedStatus:     self::strOrNull($data, 'performanceRecordedStatus'),

            ticketRating:                  self::strOrNull($data, 'ticketRating'),
            ticketRatingComment:           self::strOrNull($data, 'ticketRatingComment'),
            ticketRatingDate:              self::intOrNull($data, 'ticketRatingDate'),

            publicPageUuid:                self::strOrNull($data, 'publicPageUuid'),
            publicPageExpirationDate:      self::intOrNull($data, 'publicPageExpirationDate'),

            finishedDate:                  self::intOrNull($data, 'finishedDate'),
            followUpDate:                  self::intOrNull($data, 'followUpDate'),
            solutionDueDate:               self::intOrNull($data, 'solutionDueDate'),

            billable:                      self::bool($data, 'billable'),
            billableStatus:                self::bool($data, 'billableStatus'),
            disableEmailTemplates:         self::bool($data, 'disableEmailTemplates'),
            isTemplate:                    self::bool($data, 'isTemplate'),
            legacyTimeAndMaterialTicket:   self::bool($data, 'legacyTimeAndMaterialTicket'),
            resolvedYourIssue:             self::bool($data, 'resolvedYourIssue'),

            customAttributes:              array_map(
                static fn(array $item) => CustomAttributeDTO::fromArray($item),
                self::arr($data, 'customAttributes'),
            ),
            entityReferences:              self::arr($data, 'entityReferences'),
            tags:                          self::arr($data, 'tags'),
            watchers:                      self::arr($data, 'watchers'),
        );
    }

    /**
     * Returns true if the ticket is billable and billing has been confirmed.
     */
    public function isBilled(): bool
    {
        return $this->billable && $this->billableStatus;
    }

    /**
     * Returns the contact's full display name.
     */
    public function getContactDisplayName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    /**
     * Returns the ticket rating as a numeric value (1-5), or null if not set.
     */
    public function getRatingStars(): ?int
    {
        if ($this->ticketRating === null) {
            return null;
        }

        return match ($this->ticketRating) {
            'STARS_1' => 1,
            'STARS_2' => 2,
            'STARS_3' => 3,
            'STARS_4' => 4,
            'STARS_5' => 5,
            default   => null,
        };
    }

    /**
     * Returns the solution due date as a DateTimeImmutable object.
     */
    public function getSolutionDueDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['solutionDueDate' => $this->solutionDueDate], 'solutionDueDate');
    }

    /**
     * Returns the finished date as a DateTimeImmutable object.
     */
    public function getFinishedDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['finishedDate' => $this->finishedDate], 'finishedDate');
    }

    /**
     * Returns the creation date as a DateTimeImmutable object.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['createdDate' => $this->createdDate], 'createdDate');
    }

    /**
     * Returns the last modification date as a DateTimeImmutable object.
     */
    public function getLastModifiedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['lastModifiedDate' => $this->lastModifiedDate], 'lastModifiedDate');
    }
}
