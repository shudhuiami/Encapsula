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
    | Key Mode
    |--------------------------------------------------------------------------
    |
    | Encapsula supports two modes:
    |
    | - static: uses a single base64-encoded 32-byte key from ENCAPSULA_KEY.
    | - session: derives a per-session key via an ECDH handshake endpoint and
    |   stores it in the server session. This avoids shipping a long-term secret
    |   inside your frontend bundle.
    |
    | Note: This improves key hygiene but does not prevent an authenticated user
    | from decrypting their own responses (by design).
    |
    */

    'key_mode' => env('ENCAPSULA_KEY_MODE', 'static'),

    /*
    |--------------------------------------------------------------------------
    | Static Encryption Key (static mode)
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
    | Session Key Handshake (session mode)
    |--------------------------------------------------------------------------
    |
    | When enabled, Encapsula exposes a handshake endpoint that allows the
    | frontend to establish a per-session AES key using ECDH (P-256) + HKDF-SHA256.
    |
    | This endpoint MUST run under session middleware (typically the "web" group)
    | so the derived key can be stored server-side for subsequent responses.
    |
    */

    'handshake' => [
        'enabled' => (bool) env('ENCAPSULA_HANDSHAKE_ENABLED', false),
        'path' => env('ENCAPSULA_HANDSHAKE_PATH', '/encapsula/handshake'),

        /*
         * Middleware applied to the handshake route.
         * Keep "web" so sessions work, and add auth middleware as needed.
         */
        'middleware' => ['web'],

        /*
         * Session key name used to store the derived base64 key.
         */
        'session_key' => 'encapsula.session_key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cipher Algorithm
    |--------------------------------------------------------------------------
    |
    | The OpenSSL cipher algorithm to use. AES-256-GCM provides authenticated
    | encryption and is strongly recommended.
    |
    */

    'algorithm' => env('ENCAPSULA_ALGORITHM', 'aes-256-gcm'),

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
