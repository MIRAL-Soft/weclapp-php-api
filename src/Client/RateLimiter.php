<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Client;

use miralsoft\weclapp\api\Exception\RateLimitException;

/**
 * Wraps API calls with automatic retry logic for HTTP 429 rate-limit responses.
 *
 * Uses exponential backoff: each retry waits twice as long as the previous one,
 * starting from the Retry-After header value (or a default base delay).
 *
 * Delay schedule (with base delay of 1 second, maxRetries = 3):
 *   Attempt 1 → wait  1s  (or Retry-After value)
 *   Attempt 2 → wait  2s
 *   Attempt 3 → wait  4s
 *   Attempt 4 → throws RateLimitException
 *
 * @example
 * $limiter = new RateLimiter(maxRetries: 3);
 * $result  = $limiter->execute(fn() => $httpClient->get('article'));
 */
final class RateLimiter
{
    /**
     * @param int $maxRetries   Maximum number of retry attempts after a 429 response.
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
     * Execute the given callable, retrying automatically on HTTP 429.
     *
     * @template T
     * @param callable(): T $callable The API request to execute.
     * @return T The return value of the callable.
     *
     * @throws RateLimitException If all retry attempts are exhausted.
     * @throws \Throwable         Any non-rate-limit exception is rethrown immediately.
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
                    // All retries exhausted — rethrow to the caller
                    throw $e;
                }

                $this->wait($e, $attempt);
            }
        }
    }

    /**
     * Wait before the next retry attempt.
     *
     * Uses the Retry-After value from the exception if available,
     * otherwise calculates exponential backoff from the base delay.
     *
     * @param RateLimitException $e       The rate limit exception containing retry info.
     * @param int                $attempt The current attempt number (1-based).
     */
    private function wait(RateLimitException $e, int $attempt): void
    {
        if ($e->getRetryAfter() > 0) {
            // Use the server's recommended wait time for the first retry,
            // then apply exponential backoff on top for subsequent retries.
            $delayMs = $e->getRetryAfter() * 1000 * (2 ** ($attempt - 1));
        } else {
            // No Retry-After header — use exponential backoff from base delay
            $delayMs = $this->baseDelayMs * (2 ** ($attempt - 1));
        }

        usleep((int) ($delayMs * 1000)); // usleep expects microseconds
    }
}
