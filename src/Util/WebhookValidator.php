<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Util;

/**
 * Verifies the authenticity of incoming weclapp webhook payloads.
 *
 * weclapp signs webhook requests with an HMAC-SHA256 signature computed
 * over the raw request body using a shared secret. The signature is sent
 * in the "X-Weclapp-Signature" request header.
 *
 * Always verify webhook signatures before processing the payload to prevent
 * spoofed or tampered requests.
 *
 * @example In a Symfony controller or PSR-7 middleware:
 * $secret  = $_ENV['WECLAPP_WEBHOOK_SECRET'];
 * $payload = file_get_contents('php://input');
 * $sig     = $_SERVER['HTTP_X_WECLAPP_SIGNATURE'] ?? '';
 *
 * if (!WebhookValidator::verify($payload, $sig, $secret)) {
 *     http_response_code(401);
 *     exit('Invalid signature.');
 * }
 *
 * $event = json_decode($payload, true);
 */
final class WebhookValidator
{
    /**
     * Verify a weclapp webhook signature.
     *
     * Computes an HMAC-SHA256 digest of the raw payload with the shared secret
     * and performs a constant-time comparison against the provided signature.
     *
     * @param string $rawPayload The raw (un-decoded) request body received from weclapp.
     * @param string $signature  The value of the "X-Weclapp-Signature" request header.
     * @param string $secret     The shared secret configured in the weclapp webhook settings.
     *
     * @return bool True if the signature is valid, false otherwise.
     */
    public static function verify(string $rawPayload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawPayload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Compute the HMAC-SHA256 signature for a given payload and secret.
     *
     * Useful for generating test signatures in unit tests.
     *
     * @param string $rawPayload The raw request body to sign.
     * @param string $secret     The shared secret.
     *
     * @return string The hex-encoded HMAC-SHA256 signature.
     */
    public static function sign(string $rawPayload, string $secret): string
    {
        return hash_hmac('sha256', $rawPayload, $secret);
    }
}
