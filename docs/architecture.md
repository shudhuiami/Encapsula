# Encapsula — Package Scope and Architecture

This document defines the scope, purpose, and architecture for the Encapsula package.

---

## 1. Package Purpose

Encapsula is a Laravel API response encryption package with frontend decoding support. It provides middleware that encrypts JSON API responses using AES-256-GCM authenticated encryption, and ships TypeScript helpers for frontend decryption via the Web Crypto API.

## 2. Target Users

- Laravel developers building APIs that need an additional layer of response payload obfuscation.
- Teams that want to prevent casual inspection of API data in browser developer tools.
- Applications serving sensitive data where defense-in-depth beyond HTTPS is desired.

## 3. Package Type

**Laravel-focused Composer package** with companion frontend TypeScript helpers.

## 4. Package Identity

| Field | Value |
|---|---|
| Composer name | `zobayer/encapsula` |
| PHP namespace | `Zobayer\Encapsula` |
| Service provider | `Zobayer\Encapsula\EncapsulaServiceProvider` |
| Config file | `config/encapsula.php` |
| Middleware alias | `encapsula.encrypt` |
| License | MIT |
| Minimum PHP | 8.2 |
| Minimum Laravel | 10.0 |

## 5. Core Features

1. **Response encryption middleware** — Encrypts JSON API responses using AES-256-GCM.
2. **Encrypted envelope** — Wraps responses in a consistent envelope with payload, IV, tag, and algorithm fields.
3. **Route exclusions** — Skip encryption for specific routes by name pattern.
4. **Config-driven** — Enable/disable, key management, algorithm, and envelope field names via config.
5. **Safe skipping** — Automatically skips redirects, streamed responses, file downloads, empty responses, and non-JSON content.
6. **Frontend decryption** — TypeScript helpers for Web Crypto API decryption, Axios interceptor, and Fetch wrapper.

## 6. Non-Goals

- **Not a replacement for HTTPS.** TLS is still required.
- **Not access control.** Use Laravel authorization (policies, gates, scopes) to restrict data.
- **Not end-to-end encryption.** The server encrypts; the frontend decrypts. The server always has access to plaintext.
- **Not a key management system.** Key storage and rotation is the application's responsibility.

## 7. Architecture Overview

### Directory structure

```
encapsula/
├── config/
│   └── encapsula.php
├── src/
│   ├── Contracts/
│   │   └── Encryptor.php
│   ├── Exceptions/
│   │   └── EncryptionException.php
│   ├── Http/
│   │   └── Middleware/
│   │       └── EncryptApiResponse.php
│   ├── Services/
│   │   └── ResponseEncryptor.php
│   └── EncapsulaServiceProvider.php
├── frontend/
│   └── src/
│       ├── decrypt.ts
│       ├── axios-interceptor.ts
│       └── fetch-client.ts
├── tests/
│   ├── Unit/
│   │   └── ResponseEncryptorTest.php
│   ├── Feature/
│   │   └── EncryptApiResponseMiddlewareTest.php
│   └── TestCase.php
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── pint.json
├── LICENSE
├── README.md
├── CHANGELOG.md
├── CONTRIBUTING.md
└── AGENTS.md
```

### Data flow

```
Client Request
  │
  ▼
Laravel Router → Controller → JSON Response
  │
  ▼
EncryptApiResponse Middleware
  │
  ├─ Is JSON? Is not excluded? Is enabled?
  │   No → Pass through unchanged
  │   Yes ↓
  ├─ ResponseEncryptor::encrypt(payload)
  │   ├─ Generate random IV
  │   ├─ AES-256-GCM encrypt with key + IV
  │   └─ Return {payload, iv, tag}
  │
  ▼
Encrypted Envelope Response → Client
  │
  ▼
Frontend decrypt(envelope, key) → Original JSON
```

## 8. Security Model

- **Algorithm:** AES-256-GCM (authenticated encryption with associated data).
- **IV:** Random 12-byte nonce generated per response.
- **Tag:** 128-bit authentication tag to detect tampering.
- **Key:** 256-bit (32 bytes), base64-encoded, provided via environment variable.
- **Limitation:** The frontend must have the decryption key, so an authenticated user's browser can always access the plaintext. This is obfuscation, not true secret-keeping from the end user.

## 9. Migration from DTO Direction

The package was initially developed as a Laravel DTO/data-object package. That implementation still exists in `src/` (DataObject, DataCollection, Concerns traits) and will be removed in a follow-up PR. The encryption direction is the correct product scope going forward.

### Removal plan for DTO code

The following files are from the previous DTO direction and will be removed in the next PR:

- `src/DataObject.php`
- `src/DataCollection.php`
- `src/Concerns/HasCasting.php`
- `src/Concerns/HasFactory.php`
- `src/Concerns/HasTransformation.php`
- `src/Concerns/HasValidation.php`
- `src/Contracts/Castable.php`
- `src/Exceptions/ValidationException.php`
- `tests/Unit/DataObjectTest.php`
- `tests/Unit/DataCollectionTest.php`
- `tests/Unit/CastingTest.php`
- `tests/Unit/ValidationTest.php`

The `illuminate/validation` dependency will also be removed once DTO code is deleted.
