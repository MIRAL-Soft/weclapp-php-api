<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Query;

/**
 * Enumeration of all filter operators supported by the weclapp API v2.
 *
 * These operators are appended to field names in query strings.
 *
 * @example ?name-eq=Acme&active-eq=true&createdDate-gt=1711400000000
 *
 * @see QueryBuilder::filter()
 */
enum FilterOperator: string
{
    /** Equals: field value must match exactly. Example: ?name-eq=Acme */
    case EQ = '-eq';

    /** Not equals: field value must not match. Example: ?status-ne=CANCELLED */
    case NEQ = '-ne';

    /** Greater than. Example: ?createdDate-gt=1711400000000 */
    case GT = '-gt';

    /** Greater than or equal. Example: ?amount-ge=100 */
    case GTE = '-ge';

    /** Less than. Example: ?amount-lt=500 */
    case LT = '-lt';

    /** Less than or equal. Example: ?amount-le=500 */
    case LTE = '-le';

    /**
     * Case-insensitive contains search (LIKE with wildcards on both sides).
     * Example: ?name-ilike=acme  →  matches "Acme GmbH", "ACME Corp", etc.
     */
    case ILIKE = '-ilike';

    /**
     * Case-sensitive contains search.
     * Example: ?name-like=Acme
     */
    case LIKE = '-like';

    /**
     * Value must be one of the given comma-separated list.
     * Example: ?status-in=OPEN,CONFIRMED
     */
    case IN = '-in';

    /**
     * Value must not be in the given comma-separated list.
     * Example: ?status-not-in=CANCELLED,VOID
     */
    case NOT_IN = '-not-in';

    /**
     * Field must be null / not set.
     * No value required. Example: ?deliveryDate-is-null
     */
    case IS_NULL = '-is-null';

    /**
     * Field must not be null.
     * No value required. Example: ?deliveryDate-not-null
     */
    case NOT_NULL = '-not-null';

    /**
     * Returns true if this operator requires no value (IS NULL / NOT NULL).
     */
    public function isUnary(): bool
    {
        return match ($this) {
            self::IS_NULL, self::NOT_NULL => true,
            default                       => false,
        };
    }

    /**
     * Returns true if this operator accepts a list of values (IN / NOT IN).
     */
    public function isList(): bool
    {
        return match ($this) {
            self::IN, self::NOT_IN => true,
            default                => false,
        };
    }
}
