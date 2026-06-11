<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use miralsoft\weclapp\api\Config\WeclappConfig;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Util\ResponseParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * HTTP client wrapper for the weclapp API.
 *
 * Abstracts all raw HTTP communication via Guzzle and delegates
 * response parsing and error handling to ResponseParser.
 *
 * An optional PSR-3 logger can be injected for request/response tracing.
 * A custom GuzzleClient can be injected for testing (mock handler).
 *
 * @example
 * $http = new HttpClient($config);
 * $data = $http->get('customer', '?page=1&pageSize=50');
 */
final class HttpClient
{
    private readonly GuzzleClient   $guzzle;
    private readonly LoggerInterface $logger;

    /**
     * @param WeclappConfig          $config  The API configuration (base URL, token, timeout).
     * @param GuzzleClient|null      $guzzle  Optional Guzzle client injection (for testing).
     *                                        If null, a client is created from $config.
     * @param LoggerInterface|null   $logger  Optional PSR-3 logger for request/response tracing.
     *                                        Defaults to NullLogger (no output).
     */
    public function __construct(
        private readonly WeclappConfig $config,
        ?GuzzleClient                  $guzzle = null,
        ?LoggerInterface               $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->guzzle = $guzzle ?? new GuzzleClient([
            'timeout'         => $config->getTimeout(),
            'connect_timeout' => $config->getConnectTimeout(),
            'http_errors'     => false, // We handle errors ourselves via ResponseParser
            'headers'         => [
                'AuthenticationToken' => $config->getToken(),
                'Content-Type'        => 'application/json',
                'Accept'              => 'application/json',
            ],
        ]);
    }

    /**
     * Perform a GET request and return the decoded JSON response.
     *
     * @param string $path        API endpoint path, e.g. "customer" or "customer/123".
     * @param string $queryString URL query string including leading "?", e.g. "?page=1&pageSize=50".
     *
     * @return array<string, mixed>
     *
     * @throws WeclappApiException On any API or network error.
     */
    public function get(string $path, string $queryString = ''): array
    {
        return $this->request('GET', $path, $queryString);
    }

    /**
     * Perform a GET request and return the raw binary response body.
     *
     * Used for PDF downloads and other binary endpoints.
     *
     * @param string $path API endpoint path.
     *
     * @throws WeclappApiException On any API or network error.
     */
    public function getBinary(string $path): string
    {
        $url   = $this->buildUrl($path);
        $start = hrtime(true);

        $this->logger->debug('[weclapp] GET {path}', ['path' => $path]);

        try {
            $response = $this->guzzle->request('GET', $url);
            $elapsed  = $this->elapsed($start);

            $this->logger->debug(
                '[weclapp] {status} GET {path} ({elapsed}ms)',
                ['status' => $response->getStatusCode(), 'path' => $path, 'elapsed' => $elapsed],
            );

            return (string) $response->getBody();
        } catch (ConnectException $e) {
            $this->logger->error('[weclapp] Connection failed: {message}', ['message' => $e->getMessage()]);
            throw new WeclappApiException(
                'Connection to weclapp failed: ' . $e->getMessage(),
                0,
                $url,
            );
        } catch (RequestException $e) {
            $this->logger->error('[weclapp] Request failed: {message}', ['message' => $e->getMessage()]);
            $this->handleRequestException($e, $url);
        }
    }

    /**
     * Perform a POST request and return the decoded JSON response.
     *
     * @param string               $path    API endpoint path.
     * @param array<string, mixed> $data    Request body payload.
     * @param bool                 $dryRun  When true, appends ?dryRun=true — weclapp validates
     *                                      the request and runs business logic but does not persist.
     *                                      Returns HTTP 200 instead of 201 on success.
     *
     * @return array<string, mixed>
     *
     * @throws WeclappApiException On any API or network error.
     */
    public function post(string $path, array $data, bool $dryRun = false): array
    {
        return $this->request('POST', $path, $dryRun ? '?dryRun=true' : '', $data);
    }

    /**
     * Perform a PUT request and return the decoded JSON response.
     *
     * @param string               $path    API endpoint path.
     * @param array<string, mixed> $data    Request body payload.
     * @param bool                 $dryRun  When true, appends ?dryRun=true — weclapp validates
     *                                      the request and runs business logic but does not persist.
     *                                      Returns HTTP 200 instead of the usual 200 on success.
     *
     * @return array<string, mixed>
     *
     * @throws WeclappApiException On any API or network error.
     */
    public function put(string $path, array $data, bool $dryRun = false): array
    {
        return $this->request('PUT', $path, $dryRun ? '?dryRun=true' : '', $data);
    }

    /**
     * Perform a POST request with a raw binary body (for document uploads).
     *
     * Unlike post() which JSON-encodes the body, this method sends the raw bytes
     * as-is and sets the Content-Type to the provided mime type.
     * Used by DocumentResource to upload new documents or new document versions.
     *
     * @param string $path        API endpoint path, e.g. "document/upload".
     * @param string $queryString URL query string including leading "?".
     * @param string $binary      Raw binary content to upload (e.g. PDF bytes).
     * @param string $contentType MIME type of the uploaded file. Default: application/octet-stream.
     *
     * @return array<string, mixed> Decoded JSON response.
     *
     * @throws WeclappApiException On any API or network error.
     */
    public function postUpload(
        string $path,
        string $queryString,
        string $binary,
        string $contentType = 'application/octet-stream',
    ): array {
        $url     = $this->buildUrl($path, $queryString);
        $start   = hrtime(true);

        $this->logger->debug('[weclapp] POST (upload) {path}', ['path' => $path]);

        try {
            $response = $this->guzzle->request('POST', $url, [
                'body'    => $binary,
                'headers' => ['Content-Type' => $contentType],
            ]);

            $statusCode = $response->getStatusCode();
            $body       = (string) $response->getBody();
            $headers    = $this->extractHeaders($response);

            $this->logger->debug(
                '[weclapp] {status} POST {path} ({elapsed}ms)',
                ['status' => $statusCode, 'path' => $path, 'elapsed' => $this->elapsed($start)],
            );

            return ResponseParser::parse($statusCode, $body, $url, $headers);
        } catch (ConnectException $e) {
            $this->logger->error('[weclapp] Connection failed: {message}', ['message' => $e->getMessage()]);
            throw new WeclappApiException(
                'Connection to weclapp failed: ' . $e->getMessage(),
                0,
                $url,
            );
        } catch (RequestException $e) {
            $this->logger->error('[weclapp] Request failed: {message}', ['message' => $e->getMessage()]);
            $this->handleRequestException($e, $url);
        }
    }

    /**
     * Perform a DELETE request.
     *
     * @param string $path    API endpoint path including the resource ID, e.g. "customer/123".
     * @param bool   $dryRun  When true, appends ?dryRun=true — weclapp validates the delete
     *                        without actually removing the record. Returns HTTP 200 on success
     *                        (instead of 204); the response body is silently ignored.
     *
     * @throws WeclappApiException On any API or network error.
     */
    public function delete(string $path, bool $dryRun = false): void
    {
        $url   = $this->buildUrl($path, $dryRun ? '?dryRun=true' : '');
        $start = hrtime(true);

        $this->logger->debug('[weclapp] DELETE {path}', ['path' => $path]);

        try {
            $response   = $this->guzzle->request('DELETE', $url);
            $statusCode = $response->getStatusCode();
            $body       = (string) $response->getBody();

            $this->logger->debug(
                '[weclapp] {status} DELETE {path} ({elapsed}ms)',
                ['status' => $statusCode, 'path' => $path, 'elapsed' => $this->elapsed($start)],
            );

            // Treat 200 and 204 as success; anything else is parsed as an error
            if ($statusCode !== 200 && $statusCode !== 204) {
                ResponseParser::parse($statusCode, $body, $url, $this->extractHeaders($response));
            }
        } catch (ConnectException $e) {
            $this->logger->error('[weclapp] Connection failed: {message}', ['message' => $e->getMessage()]);
            throw new WeclappApiException(
                'Connection to weclapp failed: ' . $e->getMessage(),
                0,
                $url,
            );
        } catch (RequestException $e) {
            $this->logger->error('[weclapp] Request failed: {message}', ['message' => $e->getMessage()]);
            $this->handleRequestException($e, $url);
        }
    }

    /**
     * Build the full URL for a given API path and optional query string.
     */
    public function buildUrl(string $path, string $queryString = ''): string
    {
        return $this->config->getBaseUrl() . ltrim($path, '/') . $queryString;
    }

    /**
     * Core HTTP request method used by get(), post(), and put().
     *
     * @param string               $method      HTTP method.
     * @param string               $path        API endpoint path.
     * @param string               $queryString URL query string.
     * @param array<string,mixed>|null $body     Request body (JSON encoded).
     *
     * @return array<string, mixed>
     */
    private function request(
        string  $method,
        string  $path,
        string  $queryString = '',
        ?array  $body = null,
    ): array {
        $url     = $this->buildUrl($path, $queryString);
        $options = [];
        $start   = hrtime(true);

        $this->logger->debug('[weclapp] {method} {path}', ['method' => $method, 'path' => $path]);

        if ($body !== null) {
            $options['json'] = $body;
        }

        try {
            $response   = $this->guzzle->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $bodyString = (string) $response->getBody();
            $headers    = $this->extractHeaders($response);

            $this->logger->debug(
                '[weclapp] {status} {method} {path} ({elapsed}ms)',
                ['status' => $statusCode, 'method' => $method, 'path' => $path, 'elapsed' => $this->elapsed($start)],
            );

            return ResponseParser::parse($statusCode, $bodyString, $url, $headers);
        } catch (ConnectException $e) {
            $this->logger->error('[weclapp] Connection failed: {message}', ['message' => $e->getMessage()]);
            throw new WeclappApiException(
                'Connection to weclapp failed: ' . $e->getMessage(),
                0,
                $url,
            );
        } catch (RequestException $e) {
            $this->logger->error('[weclapp] Request failed: {message}', ['message' => $e->getMessage()]);
            $this->handleRequestException($e, $url);
        }
    }

    /**
     * Convert a Guzzle RequestException to the appropriate domain exception.
     *
     * @throws WeclappApiException Always throws.
     */
    private function handleRequestException(RequestException $e, string $url): never
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            ResponseParser::parse(
                $response->getStatusCode(),
                (string) $response->getBody(),
                $url,
                $this->extractHeaders($response),
            );
        }

        throw new WeclappApiException(
            'HTTP request failed: ' . $e->getMessage(),
            0,
            $url,
        );
    }

    /**
     * Extract response headers into a flat string map.
     *
     * @return array<string, string>
     */
    private function extractHeaders(\Psr\Http\Message\ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        return $headers;
    }

    /**
     * Calculate elapsed time in milliseconds since an hrtime() start point.
     *
     * @param int $start hrtime(true) nanosecond timestamp.
     */
    private function elapsed(int $start): int
    {
        return (int) ((hrtime(true) - $start) / 1_000_000);
    }
}
