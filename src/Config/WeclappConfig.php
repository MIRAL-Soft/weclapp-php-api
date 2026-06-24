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
 * @example Constructor:
 * $config = new WeclappConfig(
 *     tenant: 'miralsoft',
 *     token:  'your-api-token-here'
 * );
 *
 * @example From environment variables:
 * // Requires WECLAPP_TENANT and WECLAPP_TOKEN to be set.
 * $config = WeclappConfig::fromEnv();
 *
 * @example From an array (e.g. loaded from a config file):
 * $config = WeclappConfig::fromArray([
 *     'tenant' => 'miralsoft',
 *     'token'  => 'your-api-token-here',
 * ]);
 */
final class WeclappConfig
{
    /**
     * @param string $tenant         The weclapp tenant subdomain.
     *                               For "https://miralsoft.weclapp.com" pass "miralsoft".
     * @param string $token          The API authentication token (UUID format).
     * @param string $version        API version to use. Default is "v2" (recommended).
     *                               Pass "v1" only if explicitly required for legacy use.
     * @param int    $timeout        HTTP request timeout in seconds. Default: 30.
     * @param int    $connectTimeout TCP connect timeout in seconds. Default: 10.
     * @param int    $maxRetries     Maximum number of retry attempts on HTTP 429 rate limit.
     *                               Default: 3. Use 0 to disable retries.
     *
     * @throws InvalidArgumentException If tenant, token are empty or timeout/retries are invalid.
     */
    public function __construct(
        private readonly string $tenant,
        private readonly string $token,
        private readonly string $version        = 'v2',
        private readonly int    $timeout        = 30,
        private readonly int    $connectTimeout = 10,
        private readonly int    $maxRetries     = 3,
    ) {
        if (trim($tenant) === '') {
            throw new InvalidArgumentException('Weclapp tenant must not be empty.');
        }
        // The tenant is interpolated into the base URL host (https://{tenant}.weclapp.com).
        // Restricting it to subdomain-safe characters prevents a malformed tenant from
        // redirecting requests to a different host (e.g. "evil.com/").
        if (!preg_match('/^[a-z0-9][a-z0-9-]*$/i', $tenant)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Weclapp tenant "%s" is invalid: only letters, digits and hyphens are allowed '
                    . '(the subdomain part of https://{tenant}.weclapp.com).',
                    $tenant,
                ),
            );
        }
        if (trim($token) === '') {
            throw new InvalidArgumentException('Weclapp API token must not be empty.');
        }
        // The version is interpolated into the URL path (…/webapp/api/{version}/).
        if (!preg_match('/^v[0-9]+$/', $version)) {
            throw new InvalidArgumentException(
                sprintf('Weclapp API version "%s" is invalid: expected the form "v1", "v2", ….', $version),
            );
        }
        if ($timeout < 1) {
            throw new InvalidArgumentException('HTTP timeout must be at least 1 second.');
        }
        if ($connectTimeout < 1) {
            throw new InvalidArgumentException('Connect timeout must be at least 1 second.');
        }
        if ($maxRetries < 0) {
            throw new InvalidArgumentException('Max retries must be 0 or greater.');
        }
    }

    /**
     * Create a WeclappConfig from environment variables.
     *
     * Required environment variables:
     *   WECLAPP_TENANT  — Your weclapp tenant subdomain (e.g. "miralsoft").
     *   WECLAPP_TOKEN   — Your API authentication token.
     *
     * Optional environment variables:
     *   WECLAPP_VERSION         — API version, default "v2".
     *   WECLAPP_TIMEOUT         — Request timeout in seconds, default 30.
     *   WECLAPP_CONNECT_TIMEOUT — Connect timeout in seconds, default 10.
     *   WECLAPP_MAX_RETRIES     — Max retry attempts, default 3.
     *
     * @throws InvalidArgumentException If required variables are not set.
     */
    public static function fromEnv(): self
    {
        $tenant = (string) (getenv('WECLAPP_TENANT') ?: '');
        $token  = (string) (getenv('WECLAPP_TOKEN')  ?: '');

        return new self(
            tenant:         $tenant,
            token:          $token,
            version:        (string) (getenv('WECLAPP_VERSION')         ?: 'v2'),
            timeout:        (int)    (getenv('WECLAPP_TIMEOUT')         ?: 30),
            connectTimeout: (int)    (getenv('WECLAPP_CONNECT_TIMEOUT') ?: 10),
            maxRetries:     (int)    (getenv('WECLAPP_MAX_RETRIES')     ?: 3),
        );
    }

    /**
     * Create a WeclappConfig from an associative array.
     *
     * Required keys:
     *   'tenant' — Your weclapp tenant subdomain.
     *   'token'  — Your API authentication token.
     *
     * Optional keys: 'version', 'timeout', 'connectTimeout', 'maxRetries'.
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidArgumentException If required keys are missing or invalid.
     */
    public static function fromArray(array $config): self
    {
        return new self(
            tenant:         (string) ($config['tenant']         ?? ''),
            token:          (string) ($config['token']          ?? ''),
            version:        (string) ($config['version']        ?? 'v2'),
            timeout:        (int)    ($config['timeout']        ?? 30),
            connectTimeout: (int)    ($config['connectTimeout'] ?? 10),
            maxRetries:     (int)    ($config['maxRetries']     ?? 3),
        );
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
     * Returns the TCP connect timeout in seconds.
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
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
     * Builds the browser (web UI) base URL for this tenant.
     *
     * This is the host root of the weclapp web application — the detail-page
     * deep links live under `/app/...` below it. Unlike getBaseUrl() (the REST
     * API base) this is meant for human-clickable browser links, not API calls.
     *
     * Format: https://{tenant}.weclapp.com/
     *
     * @see \miralsoft\weclapp\api\Util\WebUrlBuilder
     *
     * @example "https://miralsoft.weclapp.com/"
     */
    public function getWebBaseUrl(): string
    {
        return sprintf('https://%s.weclapp.com/', $this->tenant);
    }

    /**
     * Prevent token from being exposed in var_dump / print_r output.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'tenant'         => $this->tenant,
            'token'          => '***REDACTED***',
            'version'        => $this->version,
            'timeout'        => $this->timeout,
            'connectTimeout' => $this->connectTimeout,
            'maxRetries'     => $this->maxRetries,
            'baseUrl'        => $this->getBaseUrl(),
        ];
    }
}
