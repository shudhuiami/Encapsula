# Encapsula

Laravel API response encryption middleware with frontend decoding support.

Encapsula encrypts JSON API responses at the middleware level using AES-256-GCM authenticated encryption. The frontend receives an encrypted payload envelope and decrypts it before use. This adds a layer of obfuscation to API responses visible in browser developer tools, while keeping integration simple for Laravel APIs and modern frontend applications.

> **Important:** Encapsula does not replace HTTPS. TLS is still required for transport security. This package provides application-level payload encryption as an additional layer. Authenticated users can still access the decrypted data in their browser after the frontend decrypts it.

## Packages

Encapsula has two package layers in the same repository:

| Package | Registry Link | Purpose | Path |
|---|---|---|---|
| `zobayer/encapsula` | https://packagist.org/packages/zobayer/encapsula | Laravel backend middleware for protected API responses | repository root |
| `encapsula-client` | https://www.npmjs.com/package/encapsula-client | JavaScript/TypeScript frontend decoder for Vue, React, Next.js, Nuxt, Vite, Axios, Fetch, and Node clients | `packages/client` |

For simple projects without npm, standalone copy-paste helpers are kept in `frontend/`.

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12, or 13
- OpenSSL extension with AES-256-GCM support
- Node.js/npm only if you want to use the optional frontend client package

## Backend Installation

Install the Laravel package:

```bash
composer require zobayer/encapsula
```

### Quick Setup Command (recommended)

You can scaffold the required `.env` values with:

```bash
php artisan encapsula:setup
```

Common usages:

```bash
# Static mode (default): prints env lines, including a freshly generated key
php artisan encapsula:setup --mode=static

# Session mode: prints env lines for handshake mode
php artisan encapsula:setup --mode=session --handshake

# Write into .env (won't overwrite existing keys)
php artisan encapsula:setup --mode=static --write

# Overwrite existing keys
php artisan encapsula:setup --mode=session --handshake --write --force
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

```env
ENCAPSULA_KEY=your-base64-encoded-32-byte-key
```

### Enable/Disable Encryption

To disable encryption globally at runtime (feature flag):

```env
ENCAPSULA_ENABLED=false
```

Generate a key:

```bash
php -r "echo base64_encode(random_bytes(32));"
```

### Optional: Override Algorithm

```env
ENCAPSULA_ALGORITHM=aes-256-gcm
```

### Optional: Session Key Handshake (no static frontend secret)

If you don't want to ship a long-lived `ENCAPSULA_KEY` in your frontend bundle, you can enable **session mode**.
In this mode, the frontend establishes a **per-session** AES key using ECDH (P-256) + HKDF-SHA256, and the backend stores it in the server session.

Backend `.env`:

```env
ENCAPSULA_ENABLED=true
ENCAPSULA_KEY_MODE=session
ENCAPSULA_HANDSHAKE_ENABLED=true
ENCAPSULA_KEY=
```

Important:

- The handshake route must run under **session middleware** (default is `web`).
- You should add your own auth middleware to the handshake route via `config/encapsula.php` if needed.
- After the handshake succeeds, subsequent responses encrypted by `encapsula.encrypt` will use the **session key**.

## Frontend Installation

For npm-based projects, install the client package:

```bash
npm install encapsula-client
```

Use this package in Vue, React, Next.js, Nuxt, Vite, Axios, Fetch, or Node-based frontend projects.

For non-npm projects, use the standalone helper files in:

```txt
frontend/
packages/client/examples/vanilla-js/
```

## Quick Start

### 1. Apply Middleware to Laravel Routes

```php
// In routes/api.php
Route::middleware('encapsula.encrypt')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/orders', [OrderController::class, 'index']);
});
```

### 2. Encrypted Response Format

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

### 3. Decode in Frontend with the npm Package

```ts
import { decodeEncapsulaResponse } from 'encapsula-client';
import { createEncapsulaSessionKey } from 'encapsula-client';

const key = await createEncapsulaSessionKey({ handshakeUrl: '/encapsula/handshake' });
const response = await fetch('/api/users');
const body = await response.json();

const users = await decodeEncapsulaResponse(body, {
  key,
});
```

### 4. Axios Usage

```ts
import axios from 'axios';
import { attachEncapsulaAxiosInterceptor } from 'encapsula-client';

const api = axios.create({
  baseURL: '/api',
});

attachEncapsulaAxiosInterceptor(api, {
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});

const { data: users } = await api.get('/users');
```

### 5. Fetch Wrapper Usage

```ts
import { createEncapsulaFetch } from 'encapsula-client';

const apiFetch = createEncapsulaFetch({
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});

const users = await apiFetch('/api/users');
```

### 6. Node Usage

```ts
import { decodeEncapsulaResponse } from 'encapsula-client';

const response = await fetch('https://example.com/api/users');
const body = await response.json();

const users = await decodeEncapsulaResponse(body, {
  key: process.env.ENCAPSULA_KEY,
});
```

### 7. Vanilla JavaScript Usage

For projects without npm, copy the standalone helper from:

```txt
packages/client/examples/vanilla-js/encapsula-helper.js
```

Example:

```html
<script src="encapsula-helper.js"></script>
<script>
  fetch('/api/users')
    .then((response) => response.json())
    .then((body) => Encapsula.decode(body, 'your-base64-key'))
    .then((users) => console.log(users));
</script>
```

## Configuration

After publishing (`php artisan vendor:publish --tag=encapsula-config`), edit `config/encapsula.php`:

| Option | Type | Default | Description |
|---|---|---|---|
| `enabled` | `bool` | `true` | Enable or disable response encryption globally. |
| `key_mode` | `string` | `'static'` | Key source: `'static'` (single key) or `'session'` (derived per session). |
| `key` | `string` | `env('ENCAPSULA_KEY')` | Base64-encoded 32-byte encryption key (used when `key_mode=static`). |
| `handshake.enabled` | `bool` | `false` | Enable the session handshake endpoint (required when `key_mode=session`). |
| `handshake.path` | `string` | `'/encapsula/handshake'` | Path for the handshake route. |
| `handshake.middleware` | `array` | `['web']` | Middleware applied to handshake route (must include sessions). |
| `handshake.session_key` | `string` | `'encapsula.session_key'` | Session key name used to store derived base64 AES key. |
| `algorithm` | `string` | `'aes-256-gcm'` | OpenSSL cipher algorithm (`ENCAPSULA_ALGORITHM`). |
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
- **Frontend keys are visible** in built frontend apps when they are shipped to the browser. If you want to avoid shipping a long-lived key, use the optional **session handshake mode**.
- **Key management** is the application's responsibility. Rotate keys carefully and consider a key rotation strategy for production.
- **This is obfuscation, not access control.** Use proper authorization (policies, gates, scopes) to restrict which data is returned by your API.

## Development

```bash
# Install PHP dependencies
composer install

# Run PHP tests
vendor/bin/phpunit

# Run static analysis
vendor/bin/phpstan analyse

# Check PHP code style
vendor/bin/pint --test

# Fix PHP code style
vendor/bin/pint

# Work on frontend client
cd packages/client
npm install
npm run typecheck
npm test
npm run build
```

## License

MIT. See [LICENSE](LICENSE) for details.
