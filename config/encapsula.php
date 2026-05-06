<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, unknown keys in input arrays will cause an exception
    | to be thrown during DataObject construction. Disable this to silently
    | ignore extra keys.
    |
    */

    'strict' => false,

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | The default format used when casting date strings to Carbon instances.
    |
    */

    'date_format' => 'Y-m-d H:i:s',

    /*
    |--------------------------------------------------------------------------
    | Validate By Default
    |--------------------------------------------------------------------------
    |
    | When enabled, validation rules defined on a DataObject are applied
    | automatically during construction via the from() factory method.
    |
    */

    'validate_by_default' => true,

];
