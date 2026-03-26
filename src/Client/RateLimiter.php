<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Client;

use miralsoft\weclapp\api\Exception\RateLimitException;
use miralsoft\weclapp\api\Exception\ServerException;

/**
 * Wraps API calls with automatic retry logic for HTTP 429 rate-limit and 5xx server errors.
 *
 * Uses exponential backoff: each retry waits twice as long as the previous one,
 * starting from the Retry-After header value (or a default base delay).
 * The delay is capped at 5 minutes to prevent excessively long waits.
 *
 * Delay schedule (with base delay of 1 second, maxRetries = 3):
 *   Attempt 1 → wait  1s  (or Retry-After value)
 *   Attempt 2 → wait  2s
 *   Attempt 3 → wait  4s
 *   Attempt 4 → throws RateLimitException / ServerException
 *
 * @example
 * $limiter = new RateLimiter(maxRetries: 3);
 * $result  = $limiter->execute(fn() => $httpClient->get('article'));
 */
final class RateLimiter
{
    /** Maximum delay cap in milliseconds (5 minutes). */
    private const MAX_DELAY_MS = 300_000;

    /**
     * @param int $maxRetries   Maximum number of retry attempts after a 429 or 5xx response.
     *                          Set to 0 to disable retries entirely.
     * @param int $baseDelayMs  Base delay in milliseconds for the first retry.
     *                          Subsequent retries double this value (exponential backoff).
     *                          If the API returns a Retry-After header, that value is used instead.
     */
    public function __construct(
        private readonly int $maxRetries  = 3,
        private readonly int $baseDelayMs = 1000,
    ) {}

    /**
     * Execute the given callable, retrying automatically on HTTP 429 or 5xx errors.
     *
     * @template T
     * @param callable(): T $callable The API request to execute.
     * @return T The return value of the callable.
     *
     * @throws RateLimitException If all retry attempts are exhausted after 429.
     * @throws ServerException    If all retry attempts are exhausted after 5xx.
     * @throws \Throwable         Any other exception is rethrown immediately.
     */
    public function execute(callable $callable): mixed
    {
        $attempt = 0;

        while (true) {
            try {
                return $callable();
            } catch (RateLimitException $e) {
                $attempt++;

                if ($attempt > $this->maxRetries) {
                    throw $e;
                }

                $this->waitForRateLimit($e, $attempt);
            } catch (ServerException $e) {
                $attempt++;

                if ($attempt > $this->maxRetries) {
                    throw $e;
                }

                $this->waitWithBackoff($attempt);
            }
        }
    }

    /**
     * Wait before the next retry for a 429 rate-limit response.
     *
     * Uses the Retry-After value from the exception if available,
     * otherwise calculates exponential backoff from the base delay.
     *
     * @param RateLimitException $e       The rate limit exception containing retry info.
     * @param int                $attempt The current attempt number (1-based).
     */
    private function waitForRateLimit(RateLimitException $e, int $attempt): void
    {
        if ($e->getRetryAfter() > 0) {
            // Use the server's recommended wait time, then apply exponential backoff on top.
            $delayMs = $e->getRetryAfter() * 1000 * (2 ** ($attempt - 1));
        } else {
            $delayMs = $this->baseDelayMs * (2 ** ($attempt - 1));
        }

        $this->sleep($delayMs);
    }

    /**
     * Wait before the next retry for a 5xx server error (exponential backoff).
     *
     * @param int $attempt The current attempt number (1-based).
     */
    private function waitWithBackoff(int $attempt): void
    {
        $this->sleep($this->baseDelayMs * (2 ** ($attempt - 1)));
    }

    /**
     * Sleep for the given number of milliseconds, capped at MAX_DELAY_MS.
     *
     * Using min() prevents overflow on 32-bit systems when large Retry-After
     * values are combined with exponential backoff multipliers.
     *
     * @param float|int $delayMs Desired delay in milliseconds.
     */
    private function sleep(float|int $delayMs): void
    {
        $cappedMs = (int) min($delayMs, self::MAX_DELAY_MS);
        usleep($cappedMs * 1000); // usleep expects microseconds
    }
}
