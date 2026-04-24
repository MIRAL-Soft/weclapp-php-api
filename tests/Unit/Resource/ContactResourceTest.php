<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\ContactDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ContactResource using a mocked HTTP client.
 */
class ContactResourceTest extends TestCase
{
    private function makeConfig(): WeclappConfig
    {
        return new WeclappConfig(tenant: 'test', token: 'test-token', maxRetries: 0);
    }

    private function makeClient(array $responses): WeclappClient
    {
        $mock   = new MockHandler($responses);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);

        return new WeclappClient($this->makeConfig(), guzzle: $guzzle);
    }

    /** Minimal valid contact payload matching the ContactDTO constructor. */
    private function contactPayload(
        string $id           = 'contact-1',
        string $parentPartyId = 'org-42',
    ): array {
        return [
            'id'               => $id,
            'version'          => '1',
            'createdDate'      => 1711400000000,
            'lastModifiedDate' => 1711450000000,
            'partyType'        => 'PERSON',
            'parentPartyId'    => $parentPartyId,
            'firstName'        => 'Max',
            'lastName'         => 'Mustermann',
            'email'            => 'max@example.com',
        ];
    }

    // -------------------------------------------------------------------------
    // findByParentPartyId
    // -------------------------------------------------------------------------

    public function test_find_by_parent_party_id_returns_contacts(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                'result' => [
                    $this->contactPayload('c-1', 'org-42'),
                    $this->contactPayload('c-2', 'org-42'),
                ],
            ])),
        ]);

        $contacts = $client->contacts()->findByParentPartyId('org-42');

        self::assertCount(2, $contacts);
        self::assertContainsOnlyInstancesOf(ContactDTO::class, $contacts);
        self::assertSame('c-1', $contacts[0]->id);
        self::assertSame('c-2', $contacts[1]->id);
    }

    public function test_find_by_parent_party_id_returns_empty_array_when_no_contacts(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => []])),
        ]);

        $contacts = $client->contacts()->findByParentPartyId('org-99');

        self::assertSame([], $contacts);
    }

    public function test_find_by_parent_party_id_maps_parent_party_id(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                'result' => [$this->contactPayload('c-1', 'org-42')],
            ])),
        ]);

        $contacts = $client->contacts()->findByParentPartyId('org-42');

        self::assertSame('org-42', $contacts[0]->parentPartyId);
    }

    // -------------------------------------------------------------------------
    // findByEmail
    // -------------------------------------------------------------------------

    public function test_find_by_email_returns_matching_contacts(): void
    {
        $payload         = $this->contactPayload();
        $payload['email'] = 'max@example.com';

        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => [$payload]])),
        ]);

        $contacts = $client->contacts()->findByEmail('max@example.com');

        self::assertCount(1, $contacts);
        self::assertSame('max@example.com', $contacts[0]->email);
    }

    // -------------------------------------------------------------------------
    // find (single record)
    // -------------------------------------------------------------------------

    public function test_find_by_id_returns_contact_dto(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->contactPayload('c-99'))),
        ]);

        $contact = $client->contacts()->find('c-99');

        self::assertInstanceOf(ContactDTO::class, $contact);
        self::assertSame('c-99', $contact->id);
    }

    // -------------------------------------------------------------------------
    // count
    // -------------------------------------------------------------------------

    public function test_count_returns_integer(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['result' => 17])),
        ]);

        $count = $client->contacts()->count();

        self::assertSame(17, $count);
    }
}
