<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Exception;

/**
 * Thrown when the API returns HTTP 400 Bad Request due to validation errors.
 *
 * weclapp API v2 uses strict validation — unknown fields and invalid values
 * will cause this exception. Use getErrors() to inspect the individual
 * validation failures returned by the API.
 *
 * @example
 * try {
 *     $client->customers()->create(['unknownField' => 'value']);
 * } catch (ValidationException $e) {
 *     foreach ($e->getErrors() as $error) {
 *         echo $error['field'] . ': ' . $error['message'];
 *     }
 * }
 */
class ValidationException extends WeclappApiException
{
    /** @var list<array<string, mixed>> */
    private array $errors = [];

    /**
     * Returns a new instance of this exception with the given validation errors attached.
     *
     * @param list<array<string, mixed>> $errors
     */
    public function withErrors(array $errors): static
    {
        $clone         = clone $this;
        $clone->errors = $errors;

        return $clone;
    }

    /**
     * Returns the list of validation error details returned by the weclapp API.
     *
     * Each entry typically contains a "field" key and a "message" key.
     *
     * @return list<array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
