<?php

/**
 * Integration-test bootstrap for the legacy Config class (API v1).
 *
 * Copy tests/.env.test.example to tests/.env.test and fill in your
 * tenant credentials. The file is gitignored and must never be committed.
 *
 * Required environment variables (tests/.env.test):
 *   WECLAPP_TENANT  your weclapp subdomain  (e.g. miralsoft)
 *   WECLAPP_TOKEN   your API token          (weclapp → Settings → API)
 */

use miralsoft\weclapp\api\Config;

// Load tests/.env.test if it exists (local dev override)
$envFile = __DIR__ . '/.env.test';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

$tenant = $_ENV['WECLAPP_TENANT'] ?? getenv('WECLAPP_TENANT') ?: '';
$token  = $_ENV['WECLAPP_TOKEN']  ?? getenv('WECLAPP_TOKEN')  ?: '';

if ($tenant === '' || $token === '') {
    fwrite(STDERR, "\n[weclapp] Legacy integration tests require WECLAPP_TENANT and WECLAPP_TOKEN.\n");
    fwrite(STDERR, "          Copy tests/.env.test.example → tests/.env.test and fill in your credentials.\n\n");
}

// Build the v1 URI from the tenant subdomain (legacy Config uses /api/v1/)
Config::$URI   = $tenant !== '' ? "https://{$tenant}.weclapp.com/webapp/api/v1/" : '';
Config::$TOKEN = $token;
