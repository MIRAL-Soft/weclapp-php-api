<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Util;

use miralsoft\weclapp\api\Exception\AuthenticationException;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\OptimisticLockException;
use miralsoft\weclapp\api\Exception\RateLimitException;
use miralsoft\weclapp\api\Exception\ServerException;
use miralsoft\weclapp\api\Exception\ValidationException;
use miralsoft\weclapp\api\Exception\WeclappApiException;

/**
 * Parses raw HTTP responses from the weclapp API.
 *
 * Handles JSON decoding, status-code-based exception mapping,
 * and extraction of paginated result sets.
 *
 * All methods are static — this class is a pure utility with no state.
 */
final class ResponseParser
{
    /**
     * Parse an HTTP response and return the decoded JSON body as an array.
     *
     * Inspects the HTTP status code and throws the appropriate domain exception
     * for any error status (4xx, 5xx).
     *
     * @param int    $statusCode      HTTP status code from the response.
     * @param string $body            Raw response body string.
     * @param string $url             The request URL (included in exception for debugging).
     * @param array<string,string> $headers Response headers (used for Retry-After parsing).
     *
     * @return array<string, mixed> Decoded JSON response.
     *
     * @throws AuthenticationException  On HTTP 401.
     * @throws NotFoundException        On HTTP 404.
     * @throws ValidationException      On HTTP 400 with validation errors.
     * @throws OptimisticLockException  On HTTP 409 (version conflict).
     * @throws RateLimitException       On HTTP 429.
     * @throws ServerException          On HTTP 5xx.
     * @throws WeclappApiException      On any other error status.
     */
    public static function parse(
        int    $statusCode,
        string $body,
        string $url     = '',
        array  $headers = [],
    ): array {
        if ($statusCode === 401) {
            throw new AuthenticationException(
                'Authentication failed. Verify that the API token in WeclappConfig is correct.',
                401,
                $url,
                $body,
            );
        }

        if ($statusCode === 404) {
            throw new NotFoundException(
                'The requested resource was not found in weclapp.',
                404,
                $url,
                $body,
            );
        }

        if ($statusCode === 429) {
            $retryAfter = self::parseRetryAfter($headers);
            throw new RateLimitException(
                'Rate limit exceeded. Request will be retried automatically.',
                $retryAfter,
                $url,
                $body,
            );
        }

        if ($statusCode === 409) {
            throw new OptimisticLockException(
                'Optimistic lock conflict: the record was modified by another process. Re-fetch and retry.',
                409,
                $url,
                $body,
            );
        }

        if ($statusCode === 400) {
            $data   = self::decodeJson($body, $url);
            $errors = $data['validationErrors'] ?? (isset($data['error']) ? [$data['error']] : []);
            throw (new ValidationException(
                'Validation failed: ' . ($data['message'] ?? $data['error'] ?? 'Bad request'),
                400,
                $url,
                $body,
            ))->withErrors(array_values((array) $errors));
        }

        if ($statusCode >= 500) {
            throw new ServerException(
                sprintf('weclapp server error (HTTP %d). Please try again later.', $statusCode),
                $statusCode,
                $url,
                $body,
            );
        }

        if ($statusCode >= 400) {
            $data = self::decodeJson($body, $url);
            throw new WeclappApiException(
                $data['message'] ?? sprintf('Unexpected API error (HTTP %d).', $statusCode),
                $statusCode,
                $url,
                $body,
            );
        }

        // HTTP 204 No Content (e.g. DELETE responses)
        if ($statusCode === 204 || trim($body) === '') {
            return [];
        }

        return self::decodeJson($body, $url);
    }

    /**
     * Extract the list of records from a paginated API list response.
     *
     * weclapp list responses wrap items in a "result" key:
     * {"result": [...], "recordCount": 123}
     *
     * @param array<string, mixed> $data Decoded API response.
     * @return list<array<string, mixed>>
     */
    public static function extractList(array $data): array
    {
        /** @var list<array<string, mixed>> */
        return $data['result'] ?? [];
    }

    /**
     * Extract the total record count from an API list or count response.
     *
     * Returns null if the response does not include a total count.
     *
     * @param array<string, mixed> $data Decoded API response.
     */
    public static function extractTotalCount(array $data): ?int
    {
        // Count endpoint returns {"count": 123}
        if (isset($data['count'])) {
            return (int) $data['count'];
        }

        // List endpoint may return {"recordCount": 123, "result": [...]}
        if (isset($data['recordCount'])) {
            return (int) $data['recordCount'];
        }

        return null;
    }

    /**
     * Decode a JSON string into an associative array.
     *
     * @throws WeclappApiException If JSON is invalid.
     */
    private static function decodeJson(string $body, string $url = ''): array
    {
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WeclappApiException(
                'Failed to decode weclapp API response: ' . json_last_error_msg(),
                0,
                $url,
                $body,
            );
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Parse the Retry-After header value in seconds.
     *
     * Falls back to 60 seconds if the header is missing or invalid.
     *
     * @param array<string, string> $headers
     */
    private static function parseRetryAfter(array $headers): int
    {
        $headerValue = $headers['Retry-After']
            ?? $headers['retry-after']
            ?? '';

        if ($headerValue !== '' && is_numeric($headerValue)) {
            return max(1, (int) $headerValue);
        }

        return 60;
    }
}
