<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Client;

use miralsoft\weclapp\api\Client\RateLimiter;
use miralsoft\weclapp\api\Exception\RateLimitException;
use miralsoft\weclapp\api\Exception\ServerException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RateLimiter.
 */
class RateLimiterTest extends TestCase
{
    public function test_passes_through_successful_result(): void
    {
        $limiter = new RateLimiter(maxRetries: 0);
        $result  = $limiter->execute(fn () => 'success');

        self::assertSame('success', $result);
    }

    public function test_rethrows_non_rate_limit_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unexpected');

        $limiter = new RateLimiter(maxRetries: 3);
        $limiter->execute(fn () => throw new \RuntimeException('unexpected'));
    }

    public function test_throws_rate_limit_exception_when_retries_exhausted(): void
    {
        $this->expectException(RateLimitException::class);

        $calls   = 0;
        $limiter = new RateLimiter(maxRetries: 2, baseDelayMs: 0);

        $limiter->execute(function () use (&$calls): never {
            $calls++;
            throw new RateLimitException('rate limited', 0);
        });
    }

    public function test_retries_on_rate_limit_and_succeeds(): void
    {
        $calls   = 0;
        $limiter = new RateLimiter(maxRetries: 2, baseDelayMs: 0);

        $result = $limiter->execute(function () use (&$calls): string {
            $calls++;
            if ($calls < 3) {
                throw new RateLimitException('rate limited', 0);
            }

            return 'ok';
        });

        self::assertSame('ok', $result);
        self::assertSame(3, $calls);
    }

    public function test_retry_count_matches_max_retries(): void
    {
        $calls   = 0;
        $maxRetries = 3;
        $limiter = new RateLimiter(maxRetries: $maxRetries, baseDelayMs: 0);

        try {
            $limiter->execute(function () use (&$calls): never {
                $calls++;
                throw new RateLimitException('limited', 0);
            });
        } catch (RateLimitException) {
            // Expected
        }

        // Initial attempt + 3 retries = 4 total calls
        self::assertSame($maxRetries + 1, $calls);
    }

    public function test_retries_on_server_exception(): void
    {
        $calls   = 0;
        $limiter = new RateLimiter(maxRetries: 2, baseDelayMs: 0);

        $result = $limiter->execute(function () use (&$calls): string {
            $calls++;
            if ($calls < 2) {
                throw new ServerException('server error', 500);
            }

            return 'recovered';
        });

        self::assertSame('recovered', $result);
        self::assertSame(2, $calls);
    }

    public function test_throws_server_exception_when_retries_exhausted(): void
    {
        $this->expectException(ServerException::class);

        $limiter = new RateLimiter(maxRetries: 1, baseDelayMs: 0);

        $limiter->execute(fn () => throw new ServerException('server error', 500));
    }

    public function test_zero_max_retries_rethrows_immediately(): void
    {
        $calls   = 0;
        $limiter = new RateLimiter(maxRetries: 0, baseDelayMs: 0);

        try {
            $limiter->execute(function () use (&$calls): never {
                $calls++;
                throw new RateLimitException('limited', 0);
            });
        } catch (RateLimitException) {
            // Expected
        }

        self::assertSame(1, $calls);
    }
}
