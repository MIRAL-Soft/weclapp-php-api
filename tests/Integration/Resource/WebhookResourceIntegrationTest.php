<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\WebhookDTO;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live integration tests for WebhookResource (/api/v2/webhook).
 *
 * All tests are read-only — no webhooks are registered or deleted.
 * The register() method is intentionally excluded to avoid side-effects.
 */
class WebhookResourceIntegrationTest extends IntegrationTestCase
{
    public function test_list_returns_webhook_dtos(): void
    {
        $result = $this->client()->webhooks()->list(
            QueryBuilder::new()->pageSize(10),
        );

        self::assertIsArray($result->items);
        // A tenant may have zero webhooks — assertContainsOnlyInstancesOf passes for empty arrays
        self::assertContainsOnlyInstancesOf(WebhookDTO::class, $result->items);
    }

    public function test_all_returns_webhook_dtos(): void
    {
        $webhooks = $this->client()->webhooks()->all();

        self::assertIsArray($webhooks);
        self::assertContainsOnlyInstancesOf(WebhookDTO::class, $webhooks);
    }

    public function test_count_returns_non_negative_integer(): void
    {
        $count = $this->client()->webhooks()->count();

        self::assertIsInt($count);
        self::assertGreaterThanOrEqual(0, $count);
    }

    public function test_webhook_has_required_fields(): void
    {
        $result = $this->client()->webhooks()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No webhooks registered in this tenant.');
        }

        $webhook = $result->items[0];

        self::assertNotEmpty($webhook->id);
        self::assertNotEmpty($webhook->entityName);
        self::assertNotEmpty($webhook->url);
        self::assertNotEmpty($webhook->requestMethod);
        self::assertIsInt($webhook->createdDate);
        self::assertIsBool($webhook->atCreate);
        self::assertIsBool($webhook->atUpdate);
        self::assertIsBool($webhook->atDelete);
    }

    public function test_find_by_id_returns_same_record(): void
    {
        $result = $this->client()->webhooks()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No webhooks registered in this tenant.');
        }

        $id      = $result->items[0]->id;
        $webhook = $this->client()->webhooks()->find($id);

        self::assertInstanceOf(WebhookDTO::class, $webhook);
        self::assertSame($id, $webhook->id);
    }

    public function test_active_webhooks_have_no_deactivated_date(): void
    {
        $webhooks = $this->client()->webhooks()->all();

        if (empty($webhooks)) {
            $this->markTestSkipped('No webhooks registered in this tenant.');
        }

        foreach ($webhooks as $webhook) {
            if ($webhook->isActive()) {
                self::assertNull(
                    $webhook->deactivatedDate,
                    "Webhook {$webhook->id} is considered active but has a deactivatedDate.",
                );
            }
        }
    }

    public function test_find_by_url_returns_matching_webhooks(): void
    {
        $webhooks = $this->client()->webhooks()->all();

        if (empty($webhooks)) {
            $this->markTestSkipped('No webhooks registered in this tenant.');
        }

        $url    = $webhooks[0]->url;
        $result = $this->client()->webhooks()->findByUrl($url);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertContainsOnlyInstancesOf(WebhookDTO::class, $result);

        foreach ($result as $webhook) {
            self::assertSame($url, $webhook->url);
        }
    }

    public function test_find_by_entity_name_returns_matching_webhooks(): void
    {
        $webhooks = $this->client()->webhooks()->all();

        if (empty($webhooks)) {
            $this->markTestSkipped('No webhooks registered in this tenant.');
        }

        $entityName = $webhooks[0]->entityName;
        $result     = $this->client()->webhooks()->findByEntityName($entityName);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertContainsOnlyInstancesOf(WebhookDTO::class, $result);

        foreach ($result as $webhook) {
            self::assertSame($entityName, $webhook->entityName);
        }
    }
}
