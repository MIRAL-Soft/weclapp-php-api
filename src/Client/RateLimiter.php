<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Client;

use miralsoft\weclapp\api\Exception\RateLimitException;
use miralsoft\weclapp\api\Exception\ServerException;

/**
 * Wraps API calls with automatic retry logic for HTTP 429 rate-limit and 5xx server errors.
 *
 * Uses exponential backoff with a server-enforced floor: each retry waits the
 * larger of the server's Retry-After header value and a locally calculated
 * exponential delay (doubling each attempt). The delay is capped at 5 minutes.
 *
 * Delay schedule example (base delay 1s, Retry-After = 3s, maxRetries = 3):
 *   Attempt 1 → max(3s, 1s) = 3s
 *   Attempt 2 → max(3s, 2s) = 3s
 *   Attempt 3 → max(3s, 4s) = 4s
 *   Attempt 4 → throws RateLimitException / ServerException
 *
 * For 5xx errors (no Retry-After header), pure exponential backoff is used:
 *   1s → 2s → 4s → throws ServerException
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
     * Takes the larger of the server's Retry-After value and the local exponential
     * backoff to always respect the server's recommendation while still applying
     * an increasing floor delay between retries.
     *
     * @param RateLimitException $e       The rate limit exception containing retry info.
     * @param int                $attempt The current attempt number (1-based).
     */
    private function waitForRateLimit(RateLimitException $e, int $attempt): void
    {
        $retryAfterMs = $e->getRetryAfter() * 1000;
        $backoffMs    = $this->baseDelayMs * (2 ** ($attempt - 1));

        // Use the larger of the two: always honour the server's Retry-After,
        // but fall back to local backoff if Retry-After is shorter.
        $this->sleep(max($retryAfterMs, $backoffMs));
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
