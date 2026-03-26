<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Config;

use miralsoft\weclapp\api\Config\WeclappConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WeclappConfig.
 */
class WeclappConfigTest extends TestCase
{
    public function test_creates_config_with_valid_parameters(): void
    {
        $config = new WeclappConfig(tenant: 'miralsoft', token: 'test-token-123');

        self::assertSame('miralsoft', $config->getTenant());
        self::assertSame('test-token-123', $config->getToken());
        self::assertSame('v2', $config->getVersion());
        self::assertSame(30, $config->getTimeout());
        self::assertSame(3, $config->getMaxRetries());
    }

    public function test_generates_correct_base_url(): void
    {
        $config = new WeclappConfig(tenant: 'miralsoft', token: 'test-token');

        self::assertSame(
            'https://miralsoft.weclapp.com/webapp/api/v2/',
            $config->getBaseUrl()
        );
    }

    public function test_generates_correct_base_url_for_v1(): void
    {
        $config = new WeclappConfig(tenant: 'miralsoft', token: 'test-token', version: 'v1');

        self::assertSame(
            'https://miralsoft.weclapp.com/webapp/api/v1/',
            $config->getBaseUrl()
        );
    }

    public function test_throws_on_empty_tenant(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tenant');

        new WeclappConfig(tenant: '', token: 'test-token');
    }

    public function test_throws_on_empty_token(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('token');

        new WeclappConfig(tenant: 'miralsoft', token: '');
    }

    public function test_throws_on_zero_timeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WeclappConfig(tenant: 'miralsoft', token: 'token', timeout: 0);
    }

    public function test_throws_on_negative_max_retries(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WeclappConfig(tenant: 'miralsoft', token: 'token', maxRetries: -1);
    }

    public function test_debug_info_redacts_token(): void
    {
        $config    = new WeclappConfig(tenant: 'miralsoft', token: 'super-secret-token');
        $debugInfo = $config->__debugInfo();

        self::assertSame('***REDACTED***', $debugInfo['token']);
        self::assertArrayNotHasKey('super-secret-token', $debugInfo);
    }

    public function test_allows_zero_max_retries(): void
    {
        $config = new WeclappConfig(tenant: 'miralsoft', token: 'token', maxRetries: 0);

        self::assertSame(0, $config->getMaxRetries());
    }
}
