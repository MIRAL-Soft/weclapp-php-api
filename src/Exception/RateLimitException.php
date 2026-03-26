<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Exception;

/**
 * Thrown when the API returns HTTP 429 Too Many Requests.
 *
 * The RateLimiter will automatically retry the request with exponential backoff
 * up to the configured maxRetries count. This exception is only thrown once
 * all retries are exhausted.
 *
 * @see \miralsoft\weclapp\api\Client\RateLimiter
 */
class RateLimitException extends WeclappApiException
{
    /**
     * @param string $message      Human-readable error description.
     * @param int    $retryAfter   Recommended wait time in seconds before retrying.
     *                             Parsed from the Retry-After response header if present.
     * @param string $requestUrl   The URL of the rate-limited request.
     * @param string $responseBody Raw response body.
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        private readonly int $retryAfter = 60,
        string $requestUrl   = '',
        string $responseBody = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $requestUrl, $responseBody, $previous);
    }

    /**
     * Returns the recommended number of seconds to wait before retrying.
     *
     * Falls back to 60 seconds if the API did not return a Retry-After header.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
