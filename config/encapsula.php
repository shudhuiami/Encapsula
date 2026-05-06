<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Response Encryption
    |--------------------------------------------------------------------------
    |
    | Toggle API response encryption on or off globally. When disabled,
    | the middleware passes responses through unchanged.
    |
    */

    'enabled' => (bool) env('ENCAPSULA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | A base64-encoded 32-byte (256-bit) key used for AES-256-GCM encryption.
    | Generate one with: php -r "echo base64_encode(random_bytes(32));"
    |
    | WARNING: Do not commit this value. Use an environment variable.
    |
    */

    'key' => env('ENCAPSULA_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Cipher Algorithm
    |--------------------------------------------------------------------------
    |
    | The OpenSSL cipher algorithm to use. AES-256-GCM provides authenticated
    | encryption and is strongly recommended.
    |
    */

    'algorithm' => 'aes-256-gcm',

    /*
    |--------------------------------------------------------------------------
    | Excluded Routes
    |--------------------------------------------------------------------------
    |
    | Route name patterns that should skip encryption. Supports fnmatch
    | wildcards (e.g. 'auth.*', 'health', 'debug.*').
    |
    */

    'exclude' => [
        // 'login',
        // 'health',
        // 'debug.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Envelope
    |--------------------------------------------------------------------------
    |
    | Customize the field names in the encrypted response envelope.
    |
    */

    'envelope' => [
        'encrypted_field' => 'encrypted',
        'payload_field' => 'payload',
        'iv_field' => 'iv',
        'tag_field' => 'tag',
        'algorithm_field' => 'alg',
    ],

];
