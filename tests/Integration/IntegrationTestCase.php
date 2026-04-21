<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration;

use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use PHPUnit\Framework\TestCase;

/**
 * Base class for all integration tests.
 *
 * Automatically loads tests/.env.test (if present) and creates a live
 * WeclappClient from WECLAPP_TENANT + WECLAPP_TOKEN environment variables.
 *
 * All tests in subclasses are skipped automatically when credentials are
 * missing — no failures, no network calls.
 *
 * Run integration tests explicitly:
 *   php vendor/bin/phpunit --testsuite Integration
 *
 * Setup:
 *   cp tests/.env.test.example tests/.env.test
 *   # Fill in your WECLAPP_TENANT and WECLAPP_TOKEN
 */
abstract class IntegrationTestCase extends TestCase
{
    /** Shared across all integration test classes within a single PHPUnit run. */
    private static ?WeclappClient $sharedClient = null;

    /** Tracks whether the .env.test file has already been loaded. */
    private static bool $envLoaded = false;

    /**
     * Returns the live WeclappClient.
     *
     * Marks the test as skipped (instead of failing) when WECLAPP_TENANT or
     * WECLAPP_TOKEN are not set.
     */
    final protected function client(): WeclappClient
    {
        if (self::$sharedClient !== null) {
            return self::$sharedClient;
        }

        $this->loadEnvFile();

        $tenant = (string) (getenv('WECLAPP_TENANT') ?: ($_ENV['WECLAPP_TENANT'] ?? ''));
        $token  = (string) (getenv('WECLAPP_TOKEN')  ?: ($_ENV['WECLAPP_TOKEN']  ?? ''));

        if ($tenant === '' || $token === '') {
            $this->markTestSkipped(
                'Integration tests require WECLAPP_TENANT and WECLAPP_TOKEN. ' .
                'Copy tests/.env.test.example → tests/.env.test and fill in your credentials.',
            );
        }

        self::$sharedClient = new WeclappClient(
            new WeclappConfig(tenant: $tenant, token: $token),
        );

        return self::$sharedClient;
    }

    /**
     * Load tests/.env.test once per process if the file exists.
     *
     * Lines starting with # and lines without = are ignored.
     * Values are injected via putenv() and $_ENV.
     */
    private function loadEnvFile(): void
    {
        if (self::$envLoaded) {
            return;
        }

        self::$envLoaded = true;

        $envFile = dirname(__DIR__) . '/.env.test';

        if (!is_file($envFile)) {
            return;
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}
