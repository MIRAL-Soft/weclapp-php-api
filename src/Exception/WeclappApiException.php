<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Exception;

use RuntimeException;

/**
 * Base exception for all weclapp API errors.
 *
 * All exceptions thrown by this library extend this class,
 * so callers can catch a single type if they want to handle
 * all API errors uniformly.
 *
 * @example
 * try {
 *     $customer = $client->customers()->find('123');
 * } catch (WeclappApiException $e) {
 *     echo $e->getStatusCode(); // HTTP status code
 *     echo $e->getRequestUrl(); // The URL that was called
 * }
 */
class WeclappApiException extends RuntimeException
{
    /**
     * @param string          $message      Human-readable error description.
     * @param int             $statusCode   HTTP status code returned by the API.
     * @param string          $requestUrl   The full URL of the failing request.
     * @param string          $responseBody Raw response body from the API.
     * @param \Throwable|null $previous     Previous exception for exception chaining.
     */
    public function __construct(
        string $message,
        private readonly int    $statusCode   = 0,
        private readonly string $requestUrl   = '',
        private readonly string $responseBody = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Returns the HTTP status code that triggered this exception.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the full URL that was called when the error occurred.
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * Returns the raw response body received from the API.
     *
     * Useful for debugging unexpected response formats.
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
