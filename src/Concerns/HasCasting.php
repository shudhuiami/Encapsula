<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Concerns;

use BackedEnum;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Zobayer\Encapsula\Contracts\Castable;
use Zobayer\Encapsula\DataObject;

/**
 * Provides automatic property type casting during DataObject construction.
 *
 * Reads declared constructor parameter types via reflection and casts
 * input values to match (scalars, enums, Carbon dates, nested DataObjects).
 */
trait HasCasting
{
    /**
     * Cast input values to match declared constructor parameter types.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function castProperties(array $data): array
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $data;
        }

        $parameters = $constructor->getParameters();
        $paramNames = array_map(fn (ReflectionParameter $p) => $p->getName(), $parameters);
        $unknownKeys = array_diff(array_keys($data), $paramNames);

        if (! empty($unknownKeys)) {
            if (config('encapsula.strict', false)) {
                throw new \InvalidArgumentException(
                    'Unknown keys [' . implode(', ', $unknownKeys) . '] provided to ' . static::class . '.'
                );
            }

            // Strip unknown keys so named parameter spread does not fail.
            $data = array_intersect_key($data, array_flip($paramNames));
        }

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (! array_key_exists($name, $data)) {
                continue;
            }

            $value = $data[$name];

            if ($value === null && $parameter->getType()?->allowsNull()) {
                continue;
            }

            $data[$name] = static::castValue($value, $parameter);
        }

        return $data;
    }

    /**
     * Cast a single value based on the parameter's declared type.
     */
    protected static function castValue(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Scalar types
        if (in_array($typeName, ['int', 'float', 'string', 'bool'], true)) {
            return static::castScalar($value, $typeName);
        }

        // BackedEnum
        if (is_subclass_of($typeName, BackedEnum::class)) {
            return static::castEnum($value, $typeName);
        }

        // Carbon dates
        if ($typeName === Carbon::class || is_subclass_of($typeName, Carbon::class)) {
            return static::castCarbon($value);
        }

        // Nested DataObject
        if (is_subclass_of($typeName, DataObject::class) && is_array($value)) {
            return $typeName::from($value);
        }

        // Custom Castable
        if (is_subclass_of($typeName, Castable::class)) {
            return (new $typeName())->cast($value);
        }

        return $value;
    }

    protected static function castScalar(mixed $value, string $type): int|float|string|bool
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
        };
    }

    /**
     * @param  class-string<BackedEnum>  $enumClass
     */
    protected static function castEnum(mixed $value, string $enumClass): BackedEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        return $enumClass::from($value);
    }

    protected static function castCarbon(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        $format = config('encapsula.date_format', 'Y-m-d H:i:s');

        return Carbon::createFromFormat($format, (string) $value);
    }
}
