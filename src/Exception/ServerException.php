<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Exception;

/**
 * Thrown when the API returns an HTTP 5xx server error.
 *
 * These errors originate from the weclapp platform itself and are not caused
 * by incorrect client requests. Typical causes: maintenance windows,
 * unexpected server-side failures, or temporary unavailability.
 *
 * @example
 * try {
 *     $client->articles()->list();
 * } catch (ServerException $e) {
 *     echo 'weclapp server error: HTTP ' . $e->getStatusCode();
 *     // Implement retry logic or alert
 * }
 */
class ServerException extends WeclappApiException {}
