<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Exception;

use miralsoft\weclapp\api\Exception\AuthenticationException;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\RateLimitException;
use miralsoft\weclapp\api\Exception\ServerException;
use miralsoft\weclapp\api\Exception\ValidationException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the exception hierarchy.
 */
class ExceptionHierarchyTest extends TestCase
{
    public function test_all_exceptions_extend_weclapp_api_exception(): void
    {
        self::assertInstanceOf(WeclappApiException::class, new AuthenticationException('test'));
        self::assertInstanceOf(WeclappApiException::class, new NotFoundException('test'));
        self::assertInstanceOf(WeclappApiException::class, new ValidationException('test'));
        self::assertInstanceOf(WeclappApiException::class, new RateLimitException('test'));
        self::assertInstanceOf(WeclappApiException::class, new ServerException('test'));
    }

    public function test_weclapp_api_exception_extends_runtime_exception(): void
    {
        $e = new WeclappApiException('test');

        self::assertInstanceOf(\RuntimeException::class, $e);
    }

    public function test_base_exception_stores_status_code_url_and_body(): void
    {
        $e = new WeclappApiException(
            message:      'Something went wrong',
            statusCode:   500,
            requestUrl:   'https://miralsoft.weclapp.com/webapp/api/v2/customer',
            responseBody: '{"error":"internal"}',
        );

        self::assertSame(500, $e->getStatusCode());
        self::assertSame('https://miralsoft.weclapp.com/webapp/api/v2/customer', $e->getRequestUrl());
        self::assertSame('{"error":"internal"}', $e->getResponseBody());
    }

    public function test_rate_limit_exception_stores_retry_after(): void
    {
        $e = new RateLimitException('Rate limited', retryAfter: 120);

        self::assertSame(120, $e->getRetryAfter());
        self::assertSame(429, $e->getStatusCode());
    }

    public function test_rate_limit_exception_defaults_to_60_seconds(): void
    {
        $e = new RateLimitException('Rate limited');

        self::assertSame(60, $e->getRetryAfter());
    }

    public function test_validation_exception_stores_errors(): void
    {
        $errors = [
            ['field' => 'email', 'message' => 'Invalid email format'],
            ['field' => 'company', 'message' => 'Company name is required'],
        ];

        $e = (new ValidationException('Validation failed', 400))
            ->withErrors($errors);

        self::assertSame($errors, $e->getErrors());
    }

    public function test_validation_exception_with_errors_returns_new_instance(): void
    {
        $original = new ValidationException('Validation failed');
        $withErrors = $original->withErrors([['field' => 'name']]);

        self::assertNotSame($original, $withErrors);
        self::assertSame([], $original->getErrors());
        self::assertCount(1, $withErrors->getErrors());
    }

    public function test_authentication_exception_has_correct_status(): void
    {
        $e = new AuthenticationException('Unauthorized', 401);

        self::assertSame(401, $e->getStatusCode());
    }
}
