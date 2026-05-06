<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Concerns;

use Illuminate\Support\Facades\Validator;
use Zobayer\Encapsula\Exceptions\ValidationException;

/**
 * Provides optional validation integration for DataObject construction.
 *
 * If a DataObject subclass defines a static rules() method, input data
 * is validated through Laravel's validator before construction.
 */
trait HasValidation
{
    /**
     * Validate the given data against the DataObject's rules.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>  The validated data.
     *
     * @throws ValidationException
     */
    protected static function validate(array $data): array
    {
        if (! method_exists(static::class, 'rules')) {
            return $data;
        }

        $shouldValidate = config('encapsula.validate_by_default', true);

        if (! $shouldValidate) {
            return $data;
        }

        /** @var array<string, mixed> $rules */
        $rules = static::rules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException(
                $validator->errors()->toArray(),
                $validator
            );
        }

        return $data;
    }
}
