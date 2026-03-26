<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Exception;

/**
 * Thrown when a weclapp update is rejected due to an optimistic locking conflict (HTTP 409).
 *
 * weclapp uses an integer "version" field on every entity for optimistic locking.
 * If the version you send does not match the current version on the server,
 * the update is rejected to prevent overwriting concurrent changes.
 *
 * @example
 * try {
 *     $client->customers()->update($id, ['version' => $dto->version, 'name' => 'New Name']);
 * } catch (OptimisticLockException $e) {
 *     // Re-fetch the record and retry with the latest version
 *     $fresh = $client->customers()->find($id);
 *     $client->customers()->update($id, ['version' => $fresh->version, 'name' => 'New Name']);
 * }
 */
final class OptimisticLockException extends WeclappApiException
{
    public function __construct(
        string    $message     = 'Optimistic lock conflict: the record was modified by another process.',
        int       $statusCode  = 409,
        string    $requestUrl  = '',
        string    $responseBody = '',
        ?\Throwable $previous  = null,
    ) {
        parent::__construct($message, $statusCode, $requestUrl, $responseBody, $previous);
    }
}
