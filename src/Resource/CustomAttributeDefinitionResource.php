<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\CustomAttributeDefinitionDTO;
use miralsoft\weclapp\api\Enum\CustomAttributeEntityType;
use miralsoft\weclapp\api\Enum\CustomAttributeType;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;

/**
 * Resource class for weclapp Custom Attribute Definition operations.
 *
 * Wraps the `/api/v2/customAttributeDefinition` endpoint. A definition is the
 * *schema* of a user-defined field (key, label, type, applicable entities). The
 * concrete *values* live on each entity instance as `customAttributes` and are
 * read/written via the entity resource (e.g. SalesOrderResource).
 *
 * **Key behaviours (verified against the live API):**
 *
 * - Entity scope is set via the `entities` array (e.g. `["salesOrder"]`), NOT via
 *   `attributeEntityType` — passing the latter on create fails validation.
 * - `entities` is **not** server-side filterable; {@see findByEntity()} and
 *   {@see ensure()} therefore filter the entity scope client-side after a
 *   `attributeKey` / entity query.
 * - `attributeKey` is freely choosable (e.g. "docbeeTicketId") and is the stable
 *   handle used for idempotent lookups.
 *
 * @example Ensure a text field on salesOrder (idempotent) and use its ID:
 * ```php
 * use miralsoft\weclapp\api\Enum\CustomAttributeEntityType;
 * use miralsoft\weclapp\api\Enum\CustomAttributeType;
 *
 * $def = $client->customAttributeDefinitions()->ensure(
 *     CustomAttributeEntityType::SalesOrder,
 *     'docbeeTicketId',
 *     'Docbee Ticket ID',
 *     CustomAttributeType::String,
 * );
 * $definitionId = $def->id;
 * ```
 *
 * @see \miralsoft\weclapp\api\DTO\CustomAttributeDefinitionDTO
 * @see \miralsoft\weclapp\api\DTO\CustomAttributeDTO
 */
class CustomAttributeDefinitionResource extends AbstractResource
{
    protected string $endpoint = 'customAttributeDefinition';
    protected string $dtoClass = CustomAttributeDefinitionDTO::class;

    // -------------------------------------------------------------------------
    // Typed overrides
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * @return CustomAttributeDefinitionDTO
     */
    public function find(string $id): CustomAttributeDefinitionDTO
    {
        /** @var CustomAttributeDefinitionDTO */
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return list<CustomAttributeDefinitionDTO>
     */
    public function listAll(?QueryBuilder $query = null): array
    {
        /** @var list<CustomAttributeDefinitionDTO> */
        return parent::listAll($query);
    }

    /**
     * {@inheritdoc}
     *
     * @return CustomAttributeDefinitionDTO
     */
    public function create(array $data): CustomAttributeDefinitionDTO
    {
        /** @var CustomAttributeDefinitionDTO */
        return parent::create($data);
    }

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    /**
     * Return all definitions scoped to a given entity type.
     *
     * `entities` is not server-side filterable, so all definitions are fetched
     * and filtered client-side. Use to build a setup-UI selection list.
     *
     * @param CustomAttributeEntityType|string $entity Entity type (e.g. SalesOrder).
     * @return list<CustomAttributeDefinitionDTO>
     *
     * @throws WeclappApiException
     */
    public function findByEntity(CustomAttributeEntityType|string $entity): array
    {
        $entityValue = $entity instanceof CustomAttributeEntityType ? $entity->value : $entity;

        return array_values(array_filter(
            $this->listAll(),
            static fn (CustomAttributeDefinitionDTO $def): bool => $def->appliesTo($entityValue),
        ));
    }

    /**
     * Find a single definition by its attributeKey, optionally scoped to an entity.
     *
     * @param string                                $key    The attributeKey (e.g. "docbeeTicketId").
     * @param CustomAttributeEntityType|string|null $entity Optional entity scope to disambiguate.
     * @return CustomAttributeDefinitionDTO|null
     *
     * @throws WeclappApiException
     */
    public function findByKey(string $key, CustomAttributeEntityType|string|null $entity = null): ?CustomAttributeDefinitionDTO
    {
        $matches = $this->listAll(
            QueryBuilder::new()->filterEq('attributeKey', $key)
        );

        if ($entity !== null) {
            $entityValue = $entity instanceof CustomAttributeEntityType ? $entity->value : $entity;
            $matches = array_values(array_filter(
                $matches,
                static fn (CustomAttributeDefinitionDTO $def): bool => $def->appliesTo($entityValue),
            ));
        }

        return $matches[0] ?? null;
    }

    // -------------------------------------------------------------------------
    // Idempotent ensure
    // -------------------------------------------------------------------------

    /**
     * Idempotently ensure a custom attribute definition exists for an entity.
     *
     * If a definition with the given `attributeKey` already exists for the entity
     * type, it is returned unchanged. Otherwise it is created. In both cases the
     * resulting definition (with its `id`) is returned.
     *
     * Mirrors the Docbee library's `ensureDefinition()` ergonomics.
     *
     * @param CustomAttributeEntityType|string $entity Entity type to scope to (e.g. SalesOrder).
     * @param string                           $key    Stable attributeKey (e.g. "docbeeTicketId").
     * @param string                           $label  Human-readable label.
     * @param CustomAttributeType              $type   Value type (default: STRING).
     * @return CustomAttributeDefinitionDTO            Existing or newly created definition.
     *
     * @throws \miralsoft\weclapp\api\Exception\ValidationException If creation is rejected.
     * @throws WeclappApiException
     *
     * @example
     * $def = $client->customAttributeDefinitions()->ensure(
     *     CustomAttributeEntityType::SalesOrder,
     *     'docbeeTicketId',
     *     'Docbee Ticket ID',
     * ); // second call returns the same definition, does not duplicate
     */
    public function ensure(
        CustomAttributeEntityType|string $entity,
        string $key,
        string $label,
        CustomAttributeType $type = CustomAttributeType::String,
    ): CustomAttributeDefinitionDTO {
        $existing = $this->findByKey($key, $entity);

        if ($existing !== null) {
            return $existing;
        }

        $entityValue = $entity instanceof CustomAttributeEntityType ? $entity->value : $entity;

        return $this->create([
            'entities'             => [$entityValue],
            'attributeKey'         => $key,
            'label'                => $label,
            'attributeType'        => $type->value,
            'active'               => true,
            'mandatory'            => false,
            'readOnly'             => false,
            'inheritOnCopy'        => false,
            'showInOverview'       => false,
            'showOnCreationDialog' => false,
            'defaultBooleanValue'  => false,
        ]);
    }
}
