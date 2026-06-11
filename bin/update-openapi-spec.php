<?php

declare(strict_types=1);

/**
 * Downloads the current weclapp OpenAPI v2 specification for the configured
 * tenant and writes it to openapi_v2.json in the repository root.
 *
 * Source endpoint (official, always current for the tenant):
 *   https://{tenant}.weclapp.com/webapp/api/v2/meta/openapi.json
 *
 * Credentials are read from (in order):
 *   1. Environment variables WECLAPP_TENANT / WECLAPP_TOKEN
 *   2. tests/.env.test (local development, gitignored)
 *
 * Usage:
 *   composer spec:update
 *   php bin/update-openapi-spec.php
 *
 * Notes:
 *   - The tenant meta spec only contains endpoints available to the tenant's
 *     licence. The public weclapp documentation spec is broader, but there is
 *     no stable public download URL for it.
 *   - Some live endpoints (e.g. /recurringInvoice) are missing from the spec
 *     entirely — the spec is a reference, not the complete truth. Verify
 *     critical behaviour against the live API.
 */

$root = dirname(__DIR__);

// --- Resolve credentials -----------------------------------------------------
$tenant = getenv('WECLAPP_TENANT') ?: '';
$token  = getenv('WECLAPP_TOKEN') ?: '';

if (($tenant === '' || $token === '') && is_file($root . '/tests/.env.test')) {
    foreach (file($root . '/tests/.env.test', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if ($key === 'WECLAPP_TENANT' && $tenant === '') {
            $tenant = $value;
        }
        if ($key === 'WECLAPP_TOKEN' && $token === '') {
            $token = $value;
        }
    }
}

if ($tenant === '' || $token === '') {
    fwrite(STDERR, "ERROR: WECLAPP_TENANT and WECLAPP_TOKEN must be set (env vars or tests/.env.test).\n");
    exit(1);
}

if (!preg_match('/^[a-z0-9][a-z0-9-]*$/i', $tenant)) {
    fwrite(STDERR, "ERROR: invalid tenant \"{$tenant}\".\n");
    exit(1);
}

// --- Download ----------------------------------------------------------------
$url = "https://{$tenant}.weclapp.com/webapp/api/v2/meta/openapi.json";
echo "Downloading {$url} …\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '', // accept gzip
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => ['AuthenticationToken: ' . $token],
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !is_string($body)) {
    fwrite(STDERR, "ERROR: download failed (HTTP {$code}).\n");
    exit(1);
}

// --- Validate before overwriting ----------------------------------------------
$spec = json_decode($body, true);
if (!is_array($spec) || empty($spec['paths']) || empty($spec['components']['schemas'])) {
    fwrite(STDERR, "ERROR: response is not a valid OpenAPI document — keeping the existing file.\n");
    exit(1);
}

$target = $root . '/openapi_v2.json';
$old    = is_file($target) ? json_decode((string) file_get_contents($target), true) : null;

file_put_contents($target, $body);

printf(
    "OK: openapi_v2.json updated — %d paths, %d schemas (previously: %s paths, %s schemas).\n",
    count($spec['paths']),
    count($spec['components']['schemas']),
    is_array($old) ? (string) count($old['paths'] ?? []) : '–',
    is_array($old) ? (string) count($old['components']['schemas'] ?? []) : '–',
);
