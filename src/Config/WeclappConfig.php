<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Config;

use InvalidArgumentException;

/**
 * Immutable configuration object for the Weclapp API client.
 *
 * This is the single source of truth for all connection parameters.
 * Instances are created once and passed into WeclappClient.
 *
 * @example
 * $config = new WeclappConfig(
 *     tenant: 'miralsoft',
 *     token:  'your-api-token-here'
 * );
 */
final class WeclappConfig
{
    /**
     * @param string $tenant      The weclapp tenant subdomain.
     *                            For "https://miralsoft.weclapp.com" pass "miralsoft".
     * @param string $token       The API authentication token (UUID format).
     * @param string $version     API version to use. Default is "v2" (recommended).
     *                            Pass "v1" only if explicitly required for legacy use.
     * @param int    $timeout     HTTP request timeout in seconds. Default: 30.
     * @param int    $maxRetries  Maximum number of retry attempts on HTTP 429 rate limit.
     *                            Default: 3. Use 0 to disable retries.
     *
     * @throws InvalidArgumentException If tenant, token are empty or timeout/retries are invalid.
     */
    public function __construct(
        private readonly string $tenant,
        private readonly string $token,
        private readonly string $version    = 'v2',
        private readonly int    $timeout    = 30,
        private readonly int    $maxRetries = 3,
    ) {
        if (trim($tenant) === '') {
            throw new InvalidArgumentException('Weclapp tenant must not be empty.');
        }
        if (trim($token) === '') {
            throw new InvalidArgumentException('Weclapp API token must not be empty.');
        }
        if ($timeout < 1) {
            throw new InvalidArgumentException('HTTP timeout must be at least 1 second.');
        }
        if ($maxRetries < 0) {
            throw new InvalidArgumentException('Max retries must be 0 or greater.');
        }
    }

    /**
     * Returns the tenant subdomain.
     */
    public function getTenant(): string
    {
        return $this->tenant;
    }

    /**
     * Returns the API authentication token.
     *
     * Note: The token is kept private and only exposed via this getter
     * to prevent accidental serialisation or logging.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Returns the configured API version (e.g. "v2").
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Returns the HTTP request timeout in seconds.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Returns the maximum number of rate-limit retry attempts.
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Builds and returns the full base URL for all API requests.
     *
     * Format: https://{tenant}.weclapp.com/webapp/api/{version}/
     *
     * @example "https://miralsoft.weclapp.com/webapp/api/v2/"
     */
    public function getBaseUrl(): string
    {
        return sprintf(
            'https://%s.weclapp.com/webapp/api/%s/',
            $this->tenant,
            $this->version
        );
    }

    /**
     * Prevent token from being exposed in var_dump / print_r output.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'tenant'     => $this->tenant,
            'token'      => '***REDACTED***',
            'version'    => $this->version,
            'timeout'    => $this->timeout,
            'maxRetries' => $this->maxRetries,
            'baseUrl'    => $this->getBaseUrl(),
        ];
    }
}
