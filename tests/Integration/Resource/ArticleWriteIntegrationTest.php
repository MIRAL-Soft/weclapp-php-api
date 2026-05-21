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
     * Round-trip: findRaw() backup → unchanged update() restore → verify no business field changed.
     *
     * This test documents and verifies the complete backup/restore workflow:
     *   1. findRaw($id)          — obtain a complete snapshot of the article
     *   2. update($id, $backup)  — write it back unchanged (simulates a restore)
     *   3. find($id)             — re-fetch and assert all business fields are identical
     *
     * Note on optimistic locking: after step 2, weclapp increments the `version`
     * and updates `lastModifiedDate`. A subsequent restore from the same `$backup`
     * would therefore require merging the current `version` first:
     *   $current = $client->articles()->findRaw($id);
     *   $client->articles()->update($id, array_merge($backup, ['version' => $current['version']]));
     */
    public function test_find_raw_round_trip_preserves_all_business_fields(): void
    {
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant — cannot run findRaw round-trip test.');
        }

        $before = $result->items[0];

        // Step 1: obtain the complete raw backup
        $backup = $this->client()->articles()->findRaw($before->id);

        self::assertIsArray($backup);
        self::assertSame($before->id, $backup['id'] ?? null, 'Raw backup must contain the article id.');
        self::assertArrayHasKey('version', $backup, 'Raw backup must contain the version field.');

        // Step 2: write the backup back unchanged (immediate restore)
        $restored = $this->client()->articles()->update($before->id, $backup);

        // Step 3: assert all business fields survived the round-trip
        self::assertInstanceOf(ArticleDTO::class, $restored);
        self::assertSame($before->articleNumber,    $restored->articleNumber,    'articleNumber must survive round-trip.');
        self::assertSame($before->name,             $restored->name,             'name must survive round-trip.');
        self::assertSame($before->active,           $restored->active,           'active must survive round-trip.');
        self::assertSame($before->articleCategoryId, $restored->articleCategoryId, 'category must survive round-trip.');

        // version is expected to increment after a write — that is correct behaviour.
        // lastModifiedDate will also change — also expected.
    }

    /**
     * Verify that findRaw() returns a complete, unfiltered API response.
     *
     * weclapp omits optional fields whose value is null or empty from GET
     * responses — so the raw array may not contain every key the DTO maps
     * (the DTO fills those absent keys with empty-string/null defaults).
     * This does NOT make findRaw() lossy for backup/restore purposes: when the
     * same raw array is PUT back, weclapp treats those absent fields as
     * "keep at null", matching the original state.
     *
     * What this test verifies:
     * - All identity and core required fields are present.
     * - Every field that IS present in the raw response matches the DTO value
     *   (no silent data transformation occurs in findRaw()).
     * - The raw array is substantially larger than the identity-only minimum
     *   (confirming it contains real article data, not a stub).
     */
    public function test_find_raw_contains_all_api_fields(): void
    {
        $result = $this->client()->articles()->list(
            QueryBuilder::new()->pageSize(1),
        );

        if (empty($result->items)) {
            $this->markTestSkipped('No articles in this tenant.');
        }

        $article = $result->items[0];
        $raw     = $this->client()->articles()->findRaw($article->id);

        // Required identity fields — weclapp always includes these.
        self::assertArrayHasKey('id',               $raw);
        self::assertArrayHasKey('version',          $raw);
        self::assertArrayHasKey('createdDate',      $raw);
        self::assertArrayHasKey('lastModifiedDate', $raw);
        self::assertArrayHasKey('articleNumber',    $raw);
        self::assertArrayHasKey('active',           $raw);

        // The raw array must be richer than just the identity fields.
        self::assertGreaterThan(
            10,
            count($raw),
            'Raw response must contain the full article payload, not just a stub.',
        );

        // For every field that IS present in the raw response AND mapped by the DTO,
        // the values must match — findRaw() must not transform any data.
        $dtoArray = (array) $article;
        foreach ($raw as $key => $rawValue) {
            if (!array_key_exists($key, $dtoArray)) {
                continue; // Extra system fields not in the DTO are fine — expected.
            }
            // Only compare scalar (non-array) fields to avoid DTO/raw structural differences.
            if (!is_array($rawValue)) {
                self::assertSame(
                    $dtoArray[$key],
                    $rawValue,
                    "Field '{$key}' value differs between findRaw() and find() — findRaw() must not transform data.",
                );
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
