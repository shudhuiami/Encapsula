<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
     *
     * @throws InvalidArgumentException When the source type is unsupported or JSON is invalid.
     */
    public static function from(array|Request|Model|string $source): static
    {
        $data = static::normalizeSource($source);
        $data = static::validate($data);
        $data = static::castProperties($data);

        /** @var static */
        return new static(...$data); // @phpstan-ignore new.static
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

        $decoded = json_decode($source, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException(
                'Invalid JSON string provided to '.static::class.'::from().'
            );
        }

        return $decoded;
    }
}
