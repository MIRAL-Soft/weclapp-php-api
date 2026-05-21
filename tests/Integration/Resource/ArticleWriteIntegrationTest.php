<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration\Resource;

use miralsoft\weclapp\api\DTO\ArticleDTO;
use miralsoft\weclapp\api\Exception\ValidationException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Tests\Integration\IntegrationTestCase;

/**
 * Live write integration tests for ArticleResource::patch().
 *
 * These tests create NO new records; they mutate and restore an existing article.
 * The original field value is always restored in a finally block — even if an
 * assertion fails — leaving the tenant in a pristine state after each run.
 *
 * ⚠️  Opt-in required: set WECLAPP_ALLOW_WRITES=true in tests/.env.test.
 *
 * Primary use case verified here:
 *   Trim excess whitespace in an article name (docbeeExporter repair script)
 *   without touching any other field on the record.
 */
class ArticleWriteIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->requireWrites();
    }

    /**
     * Full round-trip: load, change name only, confirm all other fields survive.
     */
    public function test_patch_name_only_leaves_all_other_fields_unchanged(): void
    {
        // Pick an existing article.
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant — cannot run patch round-trip test.');
        }

        $article     = $result->items[0];
        $originalName = $article->name;
        $testName     = '[PATCH-TEST] ' . $originalName;

        try {
            // ── Step 1: patch() — change only the name ─────────────────────────
            $patched = $this->client()->articles()->patch($article->id, [
                'name' => $testName,
            ]);

            self::assertInstanceOf(ArticleDTO::class, $patched);
            self::assertSame($testName, $patched->name, 'Patch response must reflect the new name.');

            // All other fields that the DTO maps must be unchanged in the response.
            self::assertSame($article->id,            $patched->id,            'id must not change.');
            self::assertSame($article->articleNumber, $patched->articleNumber, 'articleNumber must not change.');
            self::assertSame($article->active,        $patched->active,        'active flag must not change.');
            self::assertSame($article->articleCategoryId, $patched->articleCategoryId, 'category must not change.');

            // ── Step 2: re-fetch to confirm the change was persisted ────────────
            $reloaded = $this->client()->articles()->find($article->id);

            self::assertSame($testName, $reloaded->name, 'Name must be persisted after patch().');
            self::assertSame($article->articleNumber, $reloaded->articleNumber, 'articleNumber must still be unchanged after re-fetch.');
        } finally {
            // ── Restore the original name regardless of test outcome ────────────
            try {
                $this->client()->articles()->patch($article->id, [
                    'name' => $originalName,
                ]);
            } catch (\Throwable $e) {
                // Log but don't mask the original failure.
                fwrite(STDERR, "\n[cleanup] Could not restore article name: {$e->getMessage()}\n");
            }
        }
    }

    /**
     * Verify that patch() with ?dryRun=true validates correctly but does not persist.
     *
     * The GET inside patch() is always real; only the PUT is dry-run.
     */
    public function test_patch_dry_run_validates_but_does_not_persist(): void
    {
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant.');
        }

        $article      = $result->items[0];
        $originalName = $article->name;

        try {
            $preview = $this->client()->articles()->withDryRun()->patch($article->id, [
                'name' => '[DRY-RUN] ' . $originalName,
            ]);

            // Dry-run response has no id/version — weclapp strips them.
            self::assertSame('', $preview->id, 'Dry-run response must have empty id.');
            self::assertSame('[DRY-RUN] ' . $originalName, $preview->name, 'Dry-run response must reflect the submitted name.');
        } catch (WeclappApiException | ValidationException $e) {
            $this->markTestSkipped(
                'Endpoint rejected dry-run patch (e.g. article dryRun PUT not supported or ' .
                'insufficient permissions): ' . $e->getMessage(),
            );
        }

        // Confirm nothing was persisted.
        $reloaded = $this->client()->articles()->find($article->id);
        self::assertSame($originalName, $reloaded->name, 'Dry-run must not persist the name change.');
    }

    /**
     * Verify that patch() throws InvalidArgumentException on an empty fields array.
     */
    public function test_patch_throws_on_empty_fields(): void
    {
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant.');
        }

        $this->expectException(\InvalidArgumentException::class);

        $this->client()->articles()->patch($result->items[0]->id, []);
    }
}
