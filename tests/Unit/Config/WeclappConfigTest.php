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

    public function test_throws_on_tenant_with_host_breaking_characters(): void
    {
        // A tenant like "evil.com/" would change the request host in the base URL
        // (https://{tenant}.weclapp.com/...) — must be rejected.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tenant');

        new WeclappConfig(tenant: 'evil.com/', token: 'test-token');
    }

    public function test_throws_on_tenant_with_dot(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WeclappConfig(tenant: 'my.tenant', token: 'test-token');
    }

    public function test_allows_tenant_with_hyphen(): void
    {
        $config = new WeclappConfig(tenant: 'my-tenant-2', token: 'test-token');

        self::assertSame('https://my-tenant-2.weclapp.com/webapp/api/v2/', $config->getBaseUrl());
    }

    public function test_throws_on_invalid_version_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('version');

        new WeclappConfig(tenant: 'miralsoft', token: 'test-token', version: 'v2/../admin');
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

    public function test_connect_timeout_defaults_to_10(): void
    {
        $config = new WeclappConfig(tenant: 'miralsoft', token: 'token');

        self::assertSame(10, $config->getConnectTimeout());
    }

    public function test_connect_timeout_can_be_configured(): void
    {
        $config = new WeclappConfig(tenant: 'miralsoft', token: 'token', connectTimeout: 5);

        self::assertSame(5, $config->getConnectTimeout());
    }

    public function test_from_array_creates_config(): void
    {
        $config = WeclappConfig::fromArray([
            'tenant'  => 'miralsoft',
            'token'   => 'abc-token',
            'timeout' => 60,
        ]);

        self::assertSame('miralsoft', $config->getTenant());
        self::assertSame('abc-token', $config->getToken());
        self::assertSame(60, $config->getTimeout());
    }

    public function test_from_array_throws_on_missing_tenant(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WeclappConfig::fromArray(['token' => 'abc']);
    }

    public function test_from_env_creates_config(): void
    {
        putenv('WECLAPP_TENANT=envtenant');
        putenv('WECLAPP_TOKEN=envtoken');

        $config = WeclappConfig::fromEnv();

        self::assertSame('envtenant', $config->getTenant());
        self::assertSame('envtoken', $config->getToken());

        putenv('WECLAPP_TENANT');
        putenv('WECLAPP_TOKEN');
    }
}
