<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Exception;

/**
 * Thrown when the API returns HTTP 404 Not Found.
 *
 * Indicates that the requested resource ID does not exist in weclapp.
 *
 * @example
 * try {
 *     $customer = $client->customers()->find('non-existent-id');
 * } catch (NotFoundException $e) {
 *     // ID does not exist — handle gracefully
 * }
 */
class NotFoundException extends WeclappApiException {}
