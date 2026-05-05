<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Integration;

use miralsoft\weclapp\api\Client\WeclappClient;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\DTO\CustomerDTO;
use miralsoft\weclapp\api\DTO\SalesOrderDTO;
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

    /** Resolved test customer DTO (null = not configured or not found). */
    private static ?CustomerDTO $testCustomerCache = null;

    /** Whether testCustomer() has already been attempted in this process. */
    private static bool $testCustomerResolved = false;

    /** Resolved test sales order DTO (null = not configured or not found). */
    private static ?SalesOrderDTO $testSalesOrderCache = null;

    /** Whether testSalesOrder() has already been attempted in this process. */
    private static bool $testSalesOrderResolved = false;

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

        // Fallback: derive tenant from WECLAPP_URI (legacy env format).
        // Supports URIs like https://miralsoft.weclapp.com/webapp/api/v2/
        if ($tenant === '') {
            $uri    = (string) (getenv('WECLAPP_URI') ?: ($_ENV['WECLAPP_URI'] ?? ''));
            $host   = (string) (parse_url($uri, PHP_URL_HOST) ?? '');
            $tenant = $host !== '' ? (string) explode('.', $host)[0] : '';
        }

        if ($tenant === '' || $token === '') {
            $this->markTestSkipped(
                'Integration tests require WECLAPP_TENANT and WECLAPP_TOKEN (or WECLAPP_URI + WECLAPP_TOKEN). ' .
                'Copy tests/.env.test.example → tests/.env.test and fill in your credentials.',
            );
        }

        self::$sharedClient = new WeclappClient(
            new WeclappConfig(tenant: $tenant, token: $token),
        );

        return self::$sharedClient;
    }

    /**
     * Returns the dedicated test customer as a fully resolved CustomerDTO, or
     * null when WECLAPP_TEST_CUSTOMER_NUMBER is not set or the customer is not found.
     *
     * The customer is looked up once per PHPUnit process via findByCustomerNumber()
     * and the result is cached. Use this instead of hardcoding IDs — customer
     * numbers are stable, visible in the weclapp UI and survive data migrations.
     *
     * Configure in tests/.env.test:
     *   WECLAPP_TEST_CUSTOMER_NUMBER=K-10042
     */
    final protected function testCustomer(): ?CustomerDTO
    {
        if (self::$testCustomerResolved) {
            return self::$testCustomerCache;
        }

        self::$testCustomerResolved = true;
        $this->loadEnvFile();

        $number = trim((string) (getenv('WECLAPP_TEST_CUSTOMER_NUMBER') ?: ($_ENV['WECLAPP_TEST_CUSTOMER_NUMBER'] ?? '')));

        if ($number === '') {
            return null;
        }

        try {
            self::$testCustomerCache = $this->client()->customers()->findByCustomerNumber($number);
        } catch (\Throwable) {
            self::$testCustomerCache = null;
        }

        return self::$testCustomerCache;
    }

    /**
     * Convenience: returns the weclapp internal ID of the dedicated test customer,
     * or null when the customer is not configured or not found.
     *
     * Resolves via testCustomer() → findByCustomerNumber() under the hood.
     */
    final protected function testCustomerId(): ?string
    {
        $customer = $this->testCustomer();

        return ($customer !== null && $customer->id !== '') ? $customer->id : null;
    }

    /**
     * Returns the dedicated test sales order as a fully resolved SalesOrderDTO, or
     * null when WECLAPP_TEST_SALES_ORDER_NUMBER is not set or the order is not found.
     *
     * The order is looked up once per PHPUnit process via findByOrderNumber() and
     * the result is cached. The order should be in an open/active state (not
     * invoiced or cancelled) so that update dry-run tests can run against it.
     *
     * A second benefit: SalesOrderDTO.customerId gives a known-good customer ID
     * that can be reused for SalesOrder and Quotation create dry-run tests —
     * more reliable than picking the first customer from a list() call.
     *
     * Configure in tests/.env.test:
     *   WECLAPP_TEST_SALES_ORDER_NUMBER=SO-12345
     */
    final protected function testSalesOrder(): ?SalesOrderDTO
    {
        if (self::$testSalesOrderResolved) {
            return self::$testSalesOrderCache;
        }

        self::$testSalesOrderResolved = true;
        $this->loadEnvFile();

        $number = trim((string) (getenv('WECLAPP_TEST_SALES_ORDER_NUMBER') ?: ($_ENV['WECLAPP_TEST_SALES_ORDER_NUMBER'] ?? '')));

        if ($number === '') {
            return null;
        }

        try {
            self::$testSalesOrderCache = $this->client()->salesOrders()->findByOrderNumber($number);
        } catch (\Throwable) {
            self::$testSalesOrderCache = null;
        }

        return self::$testSalesOrderCache;
    }

    /**
     * Guard for write-enabled tests.
     *
     * Loads tests/.env.test and checks WECLAPP_ALLOW_WRITES. Marks the test
     * as skipped (not failed) when the variable is absent or false.
     *
     * Call this at the top of setUp() in any test class that writes real data:
     *
     *   protected function setUp(): void
     *   {
     *       parent::setUp();
     *       $this->requireWrites();
     *   }
     */
    final protected function requireWrites(): void
    {
        $this->loadEnvFile();

        $allowed = (string) (getenv('WECLAPP_ALLOW_WRITES') ?: ($_ENV['WECLAPP_ALLOW_WRITES'] ?? ''));

        if (!in_array(strtolower($allowed), ['true', '1', 'yes'], true)) {
            $this->markTestSkipped(
                'Write tests are disabled by default. ' .
                'Set WECLAPP_ALLOW_WRITES=true in tests/.env.test to enable them. ' .
                'Run: php vendor/bin/phpunit --testsuite Write',
            );
        }
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
