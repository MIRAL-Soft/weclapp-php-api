<?php

/**
 * Integration-test bootstrap for the legacy Config class (API v1).
 *
 * Copy tests/.env.test.example to tests/.env.test and fill in your
 * tenant credentials. The file is gitignored and must never be committed.
 *
 * Required environment variables:
 *   WECLAPP_URI   e.g. https://your-tenant.weclapp.com/webapp/api/v1/
 *   WECLAPP_TOKEN your API token (found in weclapp → Settings → API)
 */

use miralsoft\weclapp\api\Config;

// Load tests/.env.test if it exists (local dev override)
$envFile = __DIR__ . '/.env.test';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

$uri   = $_ENV['WECLAPP_URI']   ?? getenv('WECLAPP_URI')   ?: '';
$token = $_ENV['WECLAPP_TOKEN'] ?? getenv('WECLAPP_TOKEN') ?: '';

if ($uri === '' || $token === '') {
    fwrite(STDERR, "\n[weclapp] Integration tests require WECLAPP_URI and WECLAPP_TOKEN.\n");
    fwrite(STDERR, "          Copy tests/.env.test.example → tests/.env.test and fill in your credentials.\n\n");
}

Config::$URI   = $uri;
Config::$TOKEN = $token;
