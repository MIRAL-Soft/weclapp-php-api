<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\QuantityUnitDTO;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Quantity Unit operations.
 *
 * Wraps the /api/v2/unit endpoint. Units define the unit of measure used on
 * order and invoice line items (e.g. "h", "Stk.", "Lizenz"). Time-based units
 * additionally carry a timeUnitAmount (in seconds) that can be used to convert
 * tracked time to a billable quantity.
 *
 * Typical usage — populate a setup-UI dropdown with all time-based units:
 *
 * @example
 * $timeUnits = $client->quantityUnits()->findTimeUnits();
 * foreach ($timeUnits as $unit) {
 *     echo "{$unit->description} ({$unit->name})"       // "Stunde (h)"
 *        . " = {$unit->getMilliseconds()} ms\n";        // "= 3600000 ms"
 * }
 *
 * @example
 * // Look up a specific unit by name
 * $hour = $client->quantityUnits()->findByName('h');
 * echo $hour->timeUnitAmount; // 3600 (seconds)
 *
 * @see \miralsoft\weclapp\api\DTO\QuantityUnitDTO
 *
 * @extends AbstractResource<\miralsoft\weclapp\api\DTO\QuantityUnitDTO>
 */
class QuantityUnitResource extends AbstractResource
{
    protected string $endpoint = 'unit';
    protected string $dtoClass = QuantityUnitDTO::class;

    // -------------------------------------------------------------------------
    // Typed overrides (for IDE type inference)
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * @return QuantityUnitDTO
     */
    public function find(string $id): QuantityUnitDTO
    {
        /** @var QuantityUnitDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<QuantityUnitDTO>
     */
    public function listAll(?QueryBuilder $query = null): array
    {
        /** @var list<QuantityUnitDTO> */
        return parent::listAll($query);
    }

    /**
     * {@inheritdoc}
     *
     * @return QuantityUnitDTO
     */
    public function create(array $data): QuantityUnitDTO
    {
        /** @var QuantityUnitDTO */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return QuantityUnitDTO
     */
    public function update(string $id, array $data): QuantityUnitDTO
    {
        /** @var QuantityUnitDTO */
        return parent::update($id, $data);
    }

    // -------------------------------------------------------------------------
    // Convenience
    // -------------------------------------------------------------------------

    /**
     * Find a unit by its exact name (e.g. "h", "Stk.").
     *
     * @throws NotFoundException   If no unit with that name exists.
     * @throws WeclappApiException
     *
     * @example
     * $hour = $client->quantityUnits()->findByName('h');
     * echo $hour->timeUnitAmount; // 3600
     */
    public function findByName(string $name): QuantityUnitDTO
    {
        $result = $this->list(
            QueryBuilder::new()
                ->filterEq('name', $name)
                ->pageSize(1)
        );

        if (empty($result->items)) {
            throw new NotFoundException(
                sprintf('Quantity unit with name "%s" not found.', $name)
            );
        }

        /** @var QuantityUnitDTO */
        return $result->items[0];
    }

    /**
     * Return all time-based units (those that carry a timeUnitAmount).
     *
     * Filters the full unit list to only include units that have a
     * timeUnitAmount set. Use this to fill a setup-UI dropdown for
     * time-tracking sync workflows — it automatically excludes quantity
     * units (Stück, Pauschal, Lizenz, …) that are not time-based.
     *
     * Note: Jahr/Monat are not included since weclapp does not set
     * timeUnitAmount for them.
     *
     * @return list<QuantityUnitDTO>
     *
     * @throws WeclappApiException
     *
     * @example
     * $units = $client->quantityUnits()->findTimeUnits();
     * // [QuantityUnitDTO(name="h", description="Stunde", timeUnitAmount=3600), ...]
     */
    public function findTimeUnits(): array
    {
        $all = $this->listAll();

        return array_values(array_filter(
            $all,
            static fn(QuantityUnitDTO $unit): bool => $unit->isTimeUnit(),
        ));
    }
}
