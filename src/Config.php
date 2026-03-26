<?php

namespace miralsoft\weclapp\api;

/**
 * Legacy configuration class using static properties.
 *
 * @deprecated since 2.0 — use \miralsoft\weclapp\api\Config\WeclappConfig instead.
 *             Example: new WeclappConfig(tenant: 'miralsoft', token: 'your-token')
 *
 * This class remains for backward compatibility with code targeting API v1.
 * It will be removed in a future major version.
 */
class Config
{
    /** @var string The URI to the API
     *  @deprecated since 2.0 — use WeclappConfig::getBaseUrl()
     */
    public static string $URI = 'https://xxx.weclapp.com/webapp/api/v1/';

    /** @var string The Token for connection
     *  @deprecated since 2.0 — use WeclappConfig with token constructor parameter
     */
    public static string $TOKEN = 'XXX';
}