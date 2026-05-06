<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Contracts;

/**
 * Contract for custom cast classes that convert raw input to a target type.
 *
 * Implement this interface to define how a specific type should be
 * constructed from raw input data during DataObject hydration.
 */
interface Castable
{
    /**
     * Cast the given value to the target type.
     *
     * @param  mixed  $value  The raw input value to cast.
     * @return mixed The cast value.
     */
    public function cast(mixed $value): mixed;
}
