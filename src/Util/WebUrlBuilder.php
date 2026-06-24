<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Util;

use InvalidArgumentException;

/**
 * Builds canonical browser (web UI) deep-link URLs for weclapp entities.
 *
 * This is a pure URL-building helper — it makes no HTTP calls and adds no
 * authentication parameters. The produced links open the entity's detail page
 * in the weclapp web UI (useful for cross-linking from external systems).
 *
 * **URL format (verified live against the miralsoft tenant, 2026-06-12):**
 *
 *   https://{tenant}.weclapp.com/app/{segment}/{id}
 *
 * Examples confirmed by opening the pages in a logged-in browser and matching
 * the URL id against the API `id`:
 *   - salesInvoice RE28655 → /app/sales-invoice/1000372   (DTO id = 1000372 ✓)
 *   - salesOrder    4361   → /app/sales-order/488081       (DTO id = 488081  ✓)
 *
 * The `{id}` is the weclapp entity id (`SalesOrderDTO::id` / `SalesInvoiceDTO::id`).
 *
 * The entity → path-segment mapping is intentionally an explicit allow-list:
 * weclapp's UI segments are kebab-cased (`salesOrder` → `sales-order`) but only
 * the entries verified here are offered. Add new entries as they are confirmed
 * against the real UI — never guess a segment.
 *
 * @see \miralsoft\weclapp\api\Config\WeclappConfig::getWebBaseUrl()
 */
final class WebUrlBuilder
{
    /**
     * Mapping of API entity name → web UI path segment under `/app/`.
     *
     * Each entry has been verified against the live weclapp web UI. Extend only
     * with segments confirmed the same way.
     *
     * @var array<string, string>
     */
    private const ENTITY_PATHS = [
        'salesOrder'   => 'sales-order',
        'salesInvoice' => 'sales-invoice',
    ];

    /**
     * Returns true if a web UI deep link can be built for the given entity name.
     */
    public static function isSupported(string $entityName): bool
    {
        return isset(self::ENTITY_PATHS[$entityName]);
    }

    /**
     * Returns the list of entity names a web URL can currently be built for.
     *
     * @return list<string>
     */
    public static function supportedEntities(): array
    {
        return array_keys(self::ENTITY_PATHS);
    }

    /**
     * Build the browser detail-page URL for an entity.
     *
     * @param string $webBaseUrl The tenant web base, e.g. "https://miralsoft.weclapp.com/"
     *                          (from WeclappConfig::getWebBaseUrl()). A trailing
     *                          slash is optional.
     * @param string $entityName The API entity name, e.g. "salesOrder", "salesInvoice".
     * @param string $id         The weclapp entity id (the DTO `id`).
     * @return string            e.g. "https://miralsoft.weclapp.com/app/sales-order/488081"
     *
     * @throws InvalidArgumentException If the entity name is not supported, or the id is empty.
     */
    public static function build(string $webBaseUrl, string $entityName, string $id): string
    {
        if (!isset(self::ENTITY_PATHS[$entityName])) {
            throw new InvalidArgumentException(sprintf(
                'No web UI deep-link path is configured for entity "%s". Supported entities: %s.',
                $entityName,
                implode(', ', self::supportedEntities()),
            ));
        }

        if ($id === '') {
            throw new InvalidArgumentException(
                sprintf('Cannot build a web URL for entity "%s" with an empty id.', $entityName),
            );
        }

        return rtrim($webBaseUrl, '/')
            . '/app/'
            . self::ENTITY_PATHS[$entityName]
            . '/'
            . rawurlencode($id);
    }
}
