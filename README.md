# Encapsula

Laravel API response encryption middleware with frontend decoding support.

Encapsula encrypts JSON API responses at the middleware level using AES-256-GCM authenticated encryption. The frontend receives an encrypted payload envelope and decrypts it before use. This adds a layer of obfuscation to API responses visible in browser developer tools, while keeping integration simple for Laravel APIs and modern frontend applications.

> **Important:** Encapsula does not replace HTTPS. TLS is still required for transport security. This package provides application-level payload encryption as an additional layer. Authenticated users can still access the decrypted data in their browser after the frontend decrypts it.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- OpenSSL extension with AES-256-GCM support

## Installation

```bash
composer require zobayer/encapsula
```

The service provider is auto-discovered. To register manually, add to `config/app.php`:

```php
'providers' => [
    // ...
    Zobayer\Encapsula\EncapsulaServiceProvider::class,
],
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=encapsula-config
```

### Set Encryption Key

Add to your `.env` file:

```
ENCAPSULA_KEY=your-base64-encoded-32-byte-key
```

Generate a key:

```bash
php -r "echo base64_encode(random_bytes(32));"
```

## Quick Start

### Apply Middleware to Routes

```php
// In routes/api.php
Route::middleware('encapsula.encrypt')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/orders', [OrderController::class, 'index']);
});
```

### Encrypted Response Format

When middleware is active, JSON responses are wrapped in an encrypted envelope:

```json
{
    "encrypted": true,
    "payload": "base64-encoded-ciphertext",
    "iv": "base64-encoded-iv",
    "tag": "base64-encoded-auth-tag",
    "alg": "AES-256-GCM"
}
```

### Frontend Decryption

See the `frontend/` directory for TypeScript helpers:

- `frontend/src/decrypt.ts` — Core decryption function using Web Crypto API
- `frontend/src/axios-interceptor.ts` — Axios response interceptor
- `frontend/src/fetch-client.ts` — Fetch wrapper with automatic decryption

## Configuration

After publishing (`php artisan vendor:publish --tag=encapsula-config`), edit `config/encapsula.php`:

| Option | Type | Default | Description |
|---|---|---|---|
| `enabled` | `bool` | `true` | Enable or disable response encryption globally. |
| `key` | `string` | `env('ENCAPSULA_KEY')` | Base64-encoded 32-byte encryption key. |
| `algorithm` | `string` | `'aes-256-gcm'` | OpenSSL cipher algorithm. |
| `exclude` | `array` | `[]` | Route name patterns to skip encryption (e.g. `'login'`, `'health.*'`). |
| `envelope.encrypted_field` | `string` | `'encrypted'` | Name of the boolean flag field in the envelope. |
| `envelope.payload_field` | `string` | `'payload'` | Name of the ciphertext field. |
| `envelope.iv_field` | `string` | `'iv'` | Name of the initialization vector field. |
| `envelope.tag_field` | `string` | `'tag'` | Name of the authentication tag field. |
| `envelope.algorithm_field` | `string` | `'alg'` | Name of the algorithm identifier field. |

## Middleware Behavior

The middleware:

- **Encrypts** JSON responses (`Content-Type: application/json`).
- **Skips** redirects, streamed responses, binary/file downloads, empty responses, and non-JSON content.
- **Skips** routes matching the `exclude` patterns.
- **Passes through** unchanged when `enabled` is `false` or no key is configured.

## Security Limitations

- **Not a replacement for HTTPS.** Always use TLS for transport-layer security.
- **Browser-side decryption** means authenticated users can access decrypted data. This prevents casual inspection of network responses but does not hide data from the authenticated user.
- **Key management** is the application's responsibility. Rotate keys carefully and consider a key rotation strategy for production.
- **This is obfuscation, not access control.** Use proper authorization (policies, gates, scopes) to restrict which data is returned by your API.

## Development

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run static analysis
vendor/bin/phpstan analyse

# Check code style
vendor/bin/pint --test

# Fix code style
vendor/bin/pint
```

## License

MIT. See [LICENSE](LICENSE) for details.
