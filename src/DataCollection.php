<?php

declare(strict_types=1);

namespace Zobayer\Encapsula;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Typed collection wrapper for DataObject instances.
 *
 * @template T of DataObject
 *
 * @implements IteratorAggregate<int, T>
 */
class DataCollection implements Countable, IteratorAggregate
{
    /**
     * @param  array<int, T>  $items
     */
    public function __construct(
        protected array $items = []
    ) {}

    /**
     * Convert all items to plain arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn (DataObject $item) => $item->toArray(),
            $this->items
        );
    }

    /**
     * Apply a callback to each item and return a new collection.
     *
     * @template U
     *
     * @param  callable(T, int): U  $callback
     * @return array<int, U>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items, array_keys($this->items));
    }

    /**
     * Filter items using a callback and return a new DataCollection.
     *
     * @param  callable(T, int): bool  $callback
     */
    public function filter(callable $callback): static
    {
        $filtered = array_values(
            array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH)
        );

        /** @var static */
        return new static($filtered); // @phpstan-ignore new.static
    }

    /**
     * Get the number of items in the collection.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get all items as a plain array.
     *
     * @return array<int, T>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get an iterator for the items.
     *
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
