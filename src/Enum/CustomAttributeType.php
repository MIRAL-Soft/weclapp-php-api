<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Enum;

/**
 * Value types for weclapp custom attribute definitions (`customAttributeType`).
 *
 * Determines which value field on a {@see \miralsoft\weclapp\api\DTO\CustomAttributeDTO}
 * carries the actual value:
 *
 * | Type                | Value field on the customAttribute |
 * |---------------------|------------------------------------|
 * | STRING, URL, LARGE_TEXT | `stringValue`                  |
 * | INTEGER, DECIMAL    | `numberValue` (decimal string)     |
 * | BOOLEAN             | `booleanValue`                     |
 * | DATE                | `dateValue` (epoch milliseconds)   |
 * | LIST                | `selectedValueId`                  |
 * | MULTISELECT_LIST    | `selectedValues` (list of {id})    |
 * | ENTITY, REFERENCE   | `entityReferences`                 |
 *
 * @see \miralsoft\weclapp\api\Resource\CustomAttributeDefinitionResource
 */
enum CustomAttributeType: string
{
    case Boolean         = 'BOOLEAN';
    case Date            = 'DATE';
    case Decimal         = 'DECIMAL';
    case Entity          = 'ENTITY';
    case Integer         = 'INTEGER';
    case LargeText       = 'LARGE_TEXT';
    case ListType        = 'LIST';
    case MultiselectList = 'MULTISELECT_LIST';
    case Reference       = 'REFERENCE';
    case String          = 'STRING';
    case Url             = 'URL';
}
