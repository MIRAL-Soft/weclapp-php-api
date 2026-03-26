<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Exception;

/**
 * Thrown when the API returns HTTP 401 Unauthorized.
 *
 * This indicates that the configured API token is missing, invalid or expired.
 *
 * @example
 * try {
 *     $client->customers()->count();
 * } catch (AuthenticationException $e) {
 *     // Check WeclappConfig token
 * }
 */
class AuthenticationException extends WeclappApiException {}
