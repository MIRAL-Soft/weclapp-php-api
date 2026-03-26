<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Abstract base class for all weclapp API response DTOs.
 *
 * Provides a generic toArray() implementation via reflection and declares
 * the fromArray() factory contract that all concrete DTOs must implement.
 *
 * All concrete DTOs use readonly constructor properties, making instances
 * immutable once created from an API response.
 *
 * @example
 * $dto = CustomerDTO::fromArray($apiResponseData);
 * echo $dto->customerNumber;
 * $array = $dto->toArray(); // Back to plain array if needed
 */
abstract class AbstractDTO
{
    /**
     * Per-class reflection property cache to avoid repeated ReflectionClass instantiation.
     *
     * @var array<class-string, list<ReflectionProperty>>
     */
    private static array $propertyCache = [];

    /**
     * Create a DTO instance from a raw API response array.
     *
     * @param array<string, mixed> $data Raw decoded JSON from the weclapp API.
     * @return static
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Serialise the DTO back to a plain associative array.
     *
     * Uses reflection to collect all public properties.
     * Nested DTOs and DateTimeImmutable values are converted recursively.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $class = static::class;

        if (!isset(self::$propertyCache[$class])) {
            self::$propertyCache[$class] = (new ReflectionClass($this))
                ->getProperties(ReflectionProperty::IS_PUBLIC);
        }

        $result = [];

        foreach (self::$propertyCache[$class] as $property) {
            $value = $property->getValue($this);

            $result[$property->getName()] = match (true) {
                $value instanceof self            => $value->toArray(),
                $value instanceof DateTimeImmutable => $value->getTimestamp() * 1000,
                is_array($value)                  => self::convertArray($value),
                default                           => $value,
            };
        }

        return $result;
    }

    /**
     * Recursively convert array values that may contain DTOs or DateTimeImmutable.
     *
     * @param array<mixed, mixed> $array
     * @return array<mixed, mixed>
     */
    private static function convertArray(array $array): array
    {
        return array_map(static function (mixed $item): mixed {
            if ($item instanceof AbstractDTO) {
                return $item->toArray();
            }
            if ($item instanceof DateTimeImmutable) {
                return $item->getTimestamp() * 1000;
            }
            if (is_array($item)) {
                return self::convertArray($item);
            }

            return $item;
        }, $array);
    }

    /**
     * Safely extract a string value from an array, returning the default if missing or null.
     *
     * @param array<string, mixed> $data
     */
    protected static function str(array $data, string $key, string $default = ''): string
    {
        return isset($data[$key]) ? (string) $data[$key] : $default;
    }

    /**
     * Safely extract a nullable string value from an array.
     *
     * @param array<string, mixed> $data
     */
    protected static function strOrNull(array $data, string $key): ?string
    {
        return isset($data[$key]) && $data[$key] !== '' ? (string) $data[$key] : null;
    }

    /**
     * Safely extract an integer value from an array.
     *
     * @param array<string, mixed> $data
     */
    protected static function int(array $data, string $key, int $default = 0): int
    {
        return isset($data[$key]) ? (int) $data[$key] : $default;
    }

    /**
     * Safely extract a nullable integer value from an array.
     *
     * @param array<string, mixed> $data
     */
    protected static function intOrNull(array $data, string $key): ?int
    {
        return isset($data[$key]) ? (int) $data[$key] : null;
    }

    /**
     * Safely extract a float value from an array.
     *
     * @param array<string, mixed> $data
     */
    protected static function float(array $data, string $key, float $default = 0.0): float
    {
        return isset($data[$key]) ? (float) $data[$key] : $default;
    }

    /**
     * Safely extract a nullable float value from an array.
     *
     * @param array<string, mixed> $data
     */
    protected static function floatOrNull(array $data, string $key): ?float
    {
        return isset($data[$key]) ? (float) $data[$key] : null;
    }

    /**
     * Safely extract a boolean value from an array.
     *
     * @param array<string, mixed> $data
     */
    protected static function bool(array $data, string $key, bool $default = false): bool
    {
        return isset($data[$key]) ? (bool) $data[$key] : $default;
    }

    /**
     * Safely extract an array value from an array.
     *
     * @param array<string, mixed> $data
     * @return list<mixed>
     */
    protected static function arr(array $data, string $key): array
    {
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
    }

    /**
     * Convert an epoch-millisecond timestamp to DateTimeImmutable.
     *
     * Returns null if the value is missing, zero or null.
     */
    protected static function dateFromEpochMs(array $data, string $key): ?DateTimeImmutable
    {
        if (empty($data[$key])) {
            return null;
        }

        $epochSeconds = (int) ($data[$key] / 1000);

        return DateTimeImmutable::createFromFormat('U', (string) $epochSeconds) ?: null;
    }
}
