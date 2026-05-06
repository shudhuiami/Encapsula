<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Concerns;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Provides the static from() factory method for DataObject construction.
 *
 * Accepts arrays, Laravel Request objects, Eloquent Models, or JSON strings
 * and normalizes them to an associative array before construction.
 */
trait HasFactory
{
    /**
     * Create a new instance from various input types.
     *
     * @param  array<string, mixed>|Request|Model|string  $source
     * @return static
     *
     * @throws InvalidArgumentException When the source type is unsupported or JSON is invalid.
     */
    public static function from(array|Request|Model|string $source): static
    {
        $data = static::normalizeSource($source);

        if (method_exists(static::class, 'validate')) {
            $data = static::validate($data);
        }

        if (method_exists(static::class, 'castProperties')) {
            $data = static::castProperties($data);
        }

        return new static(...$data);
    }

    /**
     * Normalize the input source to an associative array.
     *
     * @param  array<string, mixed>|Request|Model|string  $source
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    protected static function normalizeSource(array|Request|Model|string $source): array
    {
        if (is_array($source)) {
            return $source;
        }

        if ($source instanceof Request) {
            return $source->all();
        }

        if ($source instanceof Model) {
            return $source->toArray();
        }

        if (is_string($source)) {
            $decoded = json_decode($source, true);

            if (! is_array($decoded)) {
                throw new InvalidArgumentException(
                    'Invalid JSON string provided to ' . static::class . '::from().'
                );
            }

            return $decoded;
        }

        throw new InvalidArgumentException(
            'Unsupported source type provided to ' . static::class . '::from().'
        );
    }
}
