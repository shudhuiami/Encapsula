<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Exceptions;

use Illuminate\Validation\Validator;
use RuntimeException;

/**
 * Thrown when DataObject input fails validation rules.
 */
class ValidationException extends RuntimeException
{
    /** @var array<string, list<string>> */
    protected array $errors;

    protected ?Validator $validator;

    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(array $errors, ?Validator $validator = null)
    {
        $this->errors = $errors;
        $this->validator = $validator;

        $firstError = collect($errors)->flatten()->first() ?? 'Validation failed.';

        parent::__construct((string) $firstError);
    }

    /**
     * Get all validation errors grouped by field.
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get the underlying Laravel validator instance, if available.
     */
    public function validator(): ?Validator
    {
        return $this->validator;
    }
}
