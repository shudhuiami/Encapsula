<?php

declare(strict_types=1);

namespace Zobayer\Encapsula;

use Zobayer\Encapsula\Concerns\HasCasting;
use Zobayer\Encapsula\Concerns\HasFactory;
use Zobayer\Encapsula\Concerns\HasTransformation;
use Zobayer\Encapsula\Concerns\HasValidation;

/**
 * Abstract base class for typed data objects (DTOs).
 *
 * Extend this class with typed constructor-promoted properties to define
 * a structured, self-documenting data object. Use readonly properties
 * for immutability.
 */
abstract class DataObject
{
    use HasFactory;
    use HasCasting;
    use HasValidation;
    use HasTransformation;

    /**
     * Create a typed collection of this data object from an array of items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return DataCollection<static>
     */
    public static function collection(array $items): DataCollection
    {
        $objects = array_map(
            fn (array $item) => static::from($item),
            $items
        );

        return new DataCollection($objects);
    }

    /**
     * Create a modified copy of this data object with the given overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return static
     */
    public function clone(array $overrides = []): static
    {
        $current = $this->toArray();

        return static::from(array_merge($current, $overrides));
    }
}
