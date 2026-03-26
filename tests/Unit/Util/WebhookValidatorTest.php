<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Util;

use miralsoft\weclapp\api\Util\WebhookValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WebhookValidator.
 */
class WebhookValidatorTest extends TestCase
{
    private const SECRET = 'my-test-secret';

    public function test_verify_returns_true_for_valid_signature(): void
    {
        $payload   = '{"eventType":"party.updated","entityId":"123"}';
        $signature = WebhookValidator::sign($payload, self::SECRET);

        self::assertTrue(WebhookValidator::verify($payload, $signature, self::SECRET));
    }

    public function test_verify_returns_false_for_wrong_signature(): void
    {
        $payload = '{"eventType":"party.updated","entityId":"123"}';

        self::assertFalse(WebhookValidator::verify($payload, 'wrong-signature', self::SECRET));
    }

    public function test_verify_returns_false_for_wrong_secret(): void
    {
        $payload   = '{"eventType":"party.updated","entityId":"123"}';
        $signature = WebhookValidator::sign($payload, self::SECRET);

        self::assertFalse(WebhookValidator::verify($payload, $signature, 'different-secret'));
    }

    public function test_verify_returns_false_for_empty_signature(): void
    {
        self::assertFalse(WebhookValidator::verify('payload', '', self::SECRET));
    }

    public function test_verify_returns_false_for_empty_secret(): void
    {
        self::assertFalse(WebhookValidator::verify('payload', 'signature', ''));
    }

    public function test_verify_returns_false_for_tampered_payload(): void
    {
        $original  = '{"eventType":"party.updated","entityId":"123"}';
        $tampered  = '{"eventType":"party.updated","entityId":"456"}';
        $signature = WebhookValidator::sign($original, self::SECRET);

        self::assertFalse(WebhookValidator::verify($tampered, $signature, self::SECRET));
    }

    public function test_sign_returns_hex_string(): void
    {
        $sig = WebhookValidator::sign('payload', self::SECRET);

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $sig);
    }

    public function test_sign_is_deterministic(): void
    {
        $payload = 'some-webhook-payload';

        self::assertSame(
            WebhookValidator::sign($payload, self::SECRET),
            WebhookValidator::sign($payload, self::SECRET),
        );
    }
}
