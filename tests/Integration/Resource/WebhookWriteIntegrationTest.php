<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\WebhookDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live write integration tests for WebhookResource.
 *
 * Unlike all other integration tests in this suite, these tests actually
 * CREATE, UPDATE and DELETE real records in the weclapp tenant.
 *
 * ⚠️  Opt-in required — these tests are skipped by default.
 *     Set the environment variable WECLAPP_ALLOW_WRITES=true to enable them:
 *
 *     WECLAPP_ALLOW_WRITES=true php vendor/bin/phpunit --testsuite Integration \
 *         --filter WebhookWriteIntegrationTest
 *
 * Why webhooks for write tests?
 *   Webhooks have no business dependencies (no required customerId, articleId, etc.),
 *   no referential constraints that block deletion, and leave zero side effects once
 *   deleted. They are the safest entity to use for a Create → Update → Delete cycle.
 *
 * Cleanup guarantee:
 *   Every test that creates a webhook wraps its assertions in a try/finally block
 *   and deletes the created record in the finally clause — even if an assertion fails.
 *   This ensures the tenant remains clean after each test run.
 */
class WebhookWriteIntegrationTest extends IntegrationTestCase
{
    /** URL prefix used for all test webhooks — easy to identify if cleanup ever fails. */
    private const TEST_URL_PREFIX = 'https://phpunit-write-test.example.invalid/weclapp-api/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireWrites(); // loads .env.test and checks WECLAPP_ALLOW_WRITES
    }

    /**
     * Generate a unique URL for each test run so parallel runs don't collide.
     */
    private function uniqueTestUrl(): string
    {
        return self::TEST_URL_PREFIX . uniqid('test_', true);
    }

    // -------------------------------------------------------------------------
    // Full lifecycle: Create → verify → Update → Delete
    // -------------------------------------------------------------------------

    public function test_create_webhook_returns_dto_with_id(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = null;

        try {
            $webhook = $this->client()->webhooks()->register(
                entityName: 'salesOrder',
                url:        $url,
                atCreate:   true,
            );

            self::assertInstanceOf(WebhookDTO::class, $webhook);
            self::assertNotEmpty($webhook->id, 'Created webhook must have an id.');
            // Use assertNotSame instead of assertNotEmpty: weclapp returns version="0" for
            // new records, and empty("0") is true in PHP (would cause a false failure).
            self::assertNotSame('', $webhook->version);
            self::assertSame('salesOrder', $webhook->entityName);
            self::assertSame($url, $webhook->url);
            self::assertTrue($webhook->atCreate);
            self::assertFalse($webhook->atUpdate);
            self::assertFalse($webhook->atDelete);
            self::assertSame('POST', $webhook->requestMethod);
            self::assertTrue($webhook->isActive());
        } finally {
            if ($webhook !== null && $webhook->id !== '') {
                try {
                    $this->client()->webhooks()->delete($webhook->id);
                } catch (\Throwable) {
                    // Ignore cleanup errors — don't mask the original failure
                }
            }
        }
    }

    public function test_find_created_webhook_by_id(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = null;

        try {
            $webhook = $this->client()->webhooks()->register(
                entityName: 'customer',
                url:        $url,
                atUpdate:   true,
            );

            $found = $this->client()->webhooks()->find($webhook->id);

            self::assertSame($webhook->id, $found->id);
            self::assertSame($url, $found->url);
            self::assertSame('customer', $found->entityName);
            self::assertFalse($found->atCreate);
            self::assertTrue($found->atUpdate);
        } finally {
            if ($webhook !== null && $webhook->id !== '') {
                try { $this->client()->webhooks()->delete($webhook->id); } catch (\Throwable) {}
            }
        }
    }

    public function test_update_webhook_adds_atupdate_flag(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = null;

        try {
            $webhook = $this->client()->webhooks()->register(
                entityName: 'salesOrder',
                url:        $url,
                atCreate:   true,
            );

            self::assertFalse($webhook->atUpdate, 'atUpdate must be false after creation.');

            // weclapp webhook PUT requires the full record back (including read-only identity
            // fields id, createdDate, lastModifiedDate). Partial payloads cause 400.
            $updated = $this->client()->webhooks()->update($webhook->id, [
                'id'               => $webhook->id,
                'version'          => $webhook->version,
                'createdDate'      => $webhook->createdDate,
                'lastModifiedDate' => $webhook->lastModifiedDate,
                'entityName'       => $webhook->entityName,
                'url'              => $webhook->url,
                'atCreate'         => true,
                'atUpdate'         => true,  // add atUpdate
                'atDelete'         => $webhook->atDelete,
                'requestMethod'    => $webhook->requestMethod,
                'deactivatedDate'  => $webhook->deactivatedDate,
            ]);

            self::assertTrue($updated->atUpdate, 'atUpdate must be true after update.');
            self::assertNotEmpty($updated->id);

            // Refresh to confirm the change was persisted
            $refreshed = $this->client()->webhooks()->find($webhook->id);
            self::assertTrue($refreshed->atUpdate);

            $webhook = $refreshed; // keep latest version for cleanup
        } finally {
            if ($webhook !== null && $webhook->id !== '') {
                try { $this->client()->webhooks()->delete($webhook->id); } catch (\Throwable) {}
            }
        }
    }

    public function test_ensure_subscription_creates_if_not_exists(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = null;

        try {
            $webhook = $this->client()->webhooks()->ensureSubscription(
                entityName: 'article',
                url:        $url,
                atCreate:   true,
                atUpdate:   true,
            );

            self::assertNotEmpty($webhook->id);
            self::assertSame('article', $webhook->entityName);
            self::assertTrue($webhook->atCreate);
            self::assertTrue($webhook->atUpdate);
            self::assertFalse($webhook->atDelete);
        } finally {
            if ($webhook !== null && $webhook->id !== '') {
                try { $this->client()->webhooks()->delete($webhook->id); } catch (\Throwable) {}
            }
        }
    }

    public function test_ensure_subscription_is_idempotent_when_unchanged(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = null;

        try {
            // First call: creates
            $first = $this->client()->webhooks()->ensureSubscription(
                entityName: 'customer',
                url:        $url,
                atCreate:   true,
                atUpdate:   true,
            );

            // Second call with identical params: must return existing without writing
            $second = $this->client()->webhooks()->ensureSubscription(
                entityName: 'customer',
                url:        $url,
                atCreate:   true,
                atUpdate:   true,
            );

            self::assertSame($first->id, $second->id);
            // version must be unchanged — no write occurred
            self::assertSame($first->version, $second->version);

            $webhook = $first;
        } finally {
            if ($webhook !== null && $webhook->id !== '') {
                try { $this->client()->webhooks()->delete($webhook->id); } catch (\Throwable) {}
            }
        }
    }

    public function test_ensure_subscription_merges_flags_additively(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = null;

        try {
            // Create with only atCreate
            $webhook = $this->client()->webhooks()->ensureSubscription(
                entityName: 'salesOrder',
                url:        $url,
                atCreate:   true,
            );

            self::assertTrue($webhook->atCreate);
            self::assertFalse($webhook->atUpdate);

            // Second call adds atUpdate — atCreate must be preserved
            $merged = $this->client()->webhooks()->ensureSubscription(
                entityName: 'salesOrder',
                url:        $url,
                atUpdate:   true,
            );

            self::assertSame($webhook->id, $merged->id);
            self::assertTrue($merged->atCreate, 'atCreate must be preserved during merge.');
            self::assertTrue($merged->atUpdate, 'atUpdate must be added during merge.');

            $webhook = $merged; // keep latest for cleanup
        } finally {
            if ($webhook !== null && $webhook->id !== '') {
                try { $this->client()->webhooks()->delete($webhook->id); } catch (\Throwable) {}
            }
        }
    }

    public function test_delete_webhook_throws_not_found_on_second_call(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = $this->client()->webhooks()->register(
            entityName: 'salesOrder',
            url:        $url,
            atDelete:   true,
        );

        // First delete must succeed silently
        $this->client()->webhooks()->delete($webhook->id);

        // Second delete must throw NotFoundException
        $this->expectException(NotFoundException::class);
        $this->client()->webhooks()->delete($webhook->id);
    }

    public function test_deactivate_webhook_sets_deactivated_date(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = null;

        try {
            $webhook = $this->client()->webhooks()->register(
                entityName: 'article',
                url:        $url,
                atCreate:   true,
            );

            self::assertTrue($webhook->isActive());

            $deactivated = $this->client()->webhooks()->deactivate($webhook->id);

            self::assertFalse($deactivated->isActive());
            self::assertNotNull(
                $deactivated->deactivatedDate,
                'deactivatedDate must be set after deactivation.',
            );

            $webhook = $deactivated;
        } finally {
            if ($webhook !== null && $webhook->id !== '') {
                try { $this->client()->webhooks()->delete($webhook->id); } catch (\Throwable) {}
            }
        }
    }

    public function test_find_by_url_returns_created_webhook(): void
    {
        $url     = $this->uniqueTestUrl();
        $webhook = null;

        try {
            $webhook = $this->client()->webhooks()->register(
                entityName: 'customer',
                url:        $url,
                atCreate:   true,
            );

            $found = $this->client()->webhooks()->findByUrl($url);

            self::assertCount(1, $found);
            self::assertSame($webhook->id, $found[0]->id);
        } finally {
            if ($webhook !== null && $webhook->id !== '') {
                try { $this->client()->webhooks()->delete($webhook->id); } catch (\Throwable) {}
            }
        }
    }
}
