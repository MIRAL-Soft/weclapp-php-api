<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\DTO;

use miralsoft\weclapp\api\DTO\WebhookEventDTO;
use miralsoft\weclapp\api\Enum\WebhookEntityName;
use miralsoft\weclapp\api\Enum\WebhookEventAction;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WebhookEventDTO.
 *
 * The fixture is the EXACT payload logged from a live weclapp delivery
 * (2026-06-11): {"entityId":"975300","entityName":"contact","type":"UPDATE"}
 */
final class WebhookEventDTOTest extends TestCase
{
    private const LIVE_PAYLOAD = '{"entityId":"975300","entityName":"contact","type":"UPDATE"}';

    public function test_from_json_parses_live_confirmed_payload(): void
    {
        $event = WebhookEventDTO::fromJson(self::LIVE_PAYLOAD);

        self::assertNotNull($event);
        self::assertSame('975300', $event->entityId);
        self::assertSame('contact', $event->entityName);
        self::assertSame('UPDATE', $event->type);
    }

    public function test_get_action_returns_typed_enum(): void
    {
        $event = WebhookEventDTO::fromJson(self::LIVE_PAYLOAD);

        self::assertSame(WebhookEventAction::Update, $event?->getAction());
    }

    public function test_get_entity_name_returns_typed_enum(): void
    {
        $event = WebhookEventDTO::fromJson(self::LIVE_PAYLOAD);

        self::assertSame(WebhookEntityName::Contact, $event?->getEntityName());
    }

    public function test_get_action_returns_null_for_unknown_type(): void
    {
        $event = WebhookEventDTO::fromArray([
            'entityId'   => '1',
            'entityName' => 'contact',
            'type'       => 'SOMETHING_NEW',
        ]);

        self::assertNull($event->getAction());
        self::assertSame('SOMETHING_NEW', $event->type); // raw value stays readable
    }

    public function test_from_json_returns_null_for_invalid_json(): void
    {
        self::assertNull(WebhookEventDTO::fromJson('not json at all'));
    }

    public function test_from_json_returns_null_for_json_without_entity_id(): void
    {
        self::assertNull(WebhookEventDTO::fromJson('{"foo":"bar"}'));
        self::assertNull(WebhookEventDTO::fromJson('{"entityId":""}'));
    }

    public function test_from_json_returns_null_for_non_object_json(): void
    {
        self::assertNull(WebhookEventDTO::fromJson('"just a string"'));
        self::assertNull(WebhookEventDTO::fromJson('42'));
    }
}
