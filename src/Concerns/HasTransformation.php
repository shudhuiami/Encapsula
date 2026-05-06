<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Concerns;

use ReflectionClass;
use ReflectionProperty;
use Zobayer\Encapsula\DataCollection;
use Zobayer\Encapsula\DataObject;

/**
 * Provides toArray() serialization for DataObject instances.
 *
 * Iterates public properties and recursively converts nested
 * DataObjects and DataCollections to plain arrays.
 */
trait HasTransformation
{
    /**
     * Convert the data object to a plain associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];

        foreach ($properties as $property) {
            $name = $property->getName();

            if (! $property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);
            $result[$name] = static::transformValue($value);
        }

        return $result;
    }

    /**
     * Recursively transform a value for array output.
     */
    protected static function transformValue(mixed $value): mixed
    {
        if ($value instanceof DataObject) {
            return $value->toArray();
        }

        if ($value instanceof DataCollection) {
            return $value->toArray();
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(config('encapsula.date_format', 'Y-m-d H:i:s'));
        }

        if (is_array($value)) {
            return array_map(fn (mixed $v) => static::transformValue($v), $value);
        }

        return $value;
    }
}
