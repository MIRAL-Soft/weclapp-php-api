<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\NumberRangeValueDTO;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Number Range Value operations.
 *
 * NumberRangeValues hold the concrete counter configuration for a number
 * range: its prefix (e.g. "PR-"), suffix, current counter and optional
 * validity period.
 *
 * This endpoint is read-only in the API (GET only).
 *
 * Wraps the /api/v2/numberRangeValue endpoint.
 *
 * @see \miralsoft\weclapp\api\DTO\NumberRangeValueDTO
 * @see \miralsoft\weclapp\api\Resource\NumberRangeResource
 */
class NumberRangeValueResource extends AbstractResource
{
    protected string $endpoint = 'numberRangeValue';
    protected string $dtoClass = NumberRangeValueDTO::class;

    /**
     * Retrieve a single NumberRangeValue by its weclapp ID.
     *
     * @throws \miralsoft\weclapp\api\Exception\NotFoundException
     * @throws WeclappApiException
     */
    public function find(string $id): NumberRangeValueDTO
    {
        /** @var NumberRangeValueDTO */
        return parent::find($id);
    }

    /**
     * Retrieve all NumberRangeValues belonging to a given NumberRange.
     *
     * Multiple values can exist for one range (e.g. different prefixes per
     * sales channel or different prefixes for different validity periods).
     *
     * @return list<NumberRangeValueDTO>
     *
     * @throws WeclappApiException
     */
    public function findByNumberRange(string $numberRangeId): array
    {
        /** @var list<NumberRangeValueDTO> */
        return $this->listAll(
            QueryBuilder::new()->filterEq('numberRangeId', $numberRangeId)
        );
    }

    /**
     * Returns the prefix string configured for the given NumberRange.
     *
     * When multiple values exist, the currently active one (validity period
     * includes today) is preferred. If no active value exists, the first
     * value is used as a fallback. Returns null if no values are found or
     * none of them have a prefix configured.
     *
     * @throws WeclappApiException
     */
    public function findPrefix(string $numberRangeId): ?string
    {
        $values = $this->findByNumberRange($numberRangeId);

        if (empty($values)) {
            return null;
        }

        // Prefer the currently active value
        $active = array_values(array_filter(
            $values,
            static fn(NumberRangeValueDTO $v): bool => $v->isCurrentlyActive()
        ));

        $target = $active[0] ?? $values[0];

        return $target->prefix;
    }
}
