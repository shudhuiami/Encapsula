# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **Package direction changed** from DTO/data-object to API response encryption middleware with frontend decoding.
- Updated `composer.json` description and keywords.
- Allow Laravel 13 / Illuminate 13 in Composer constraints.
- Updated `README.md` with encryption-focused documentation.
- Updated `docs/architecture.md` with new scope and architecture.
- Replaced `config/encapsula.php` with encryption-focused settings.
- Updated `EncapsulaServiceProvider` to bind encryption contracts and register middleware.

### Added

- `Encryptor` contract (`src/Contracts/Encryptor.php`).
- `ResponseEncryptor` service using AES-256-GCM (`src/Services/ResponseEncryptor.php`).
- `EncryptApiResponse` middleware (`src/Http/Middleware/EncryptApiResponse.php`).
- Optional session-key handshake mode (ECDH P-256 + HKDF-SHA256) to avoid shipping a long-lived frontend secret.
- Frontend helper `createEncapsulaSessionKey()` for session handshake mode.
- `EncryptionException` for encryption/decryption failures (`src/Exceptions/EncryptionException.php`).
- Frontend TypeScript helpers: `decrypt.ts`, `axios-interceptor.ts`, `fetch-client.ts`.
- Unit tests for `ResponseEncryptor`.
- Feature tests for `EncryptApiResponse` middleware.

### Deprecated

- DTO/DataObject classes (`DataObject`, `DataCollection`, Concerns traits) are deprecated and will be removed in the next release.

## [0.1.0] - 2026-05-06 [YANKED]

### Added

- `DataObject` abstract base class with typed constructor-promoted properties.
- `DataObject::from()` static factory accepting arrays, Laravel Request, Eloquent Model, or JSON strings.
- `DataObject::toArray()` for recursive serialization to plain arrays.
- `DataObject::clone()` for creating modified copies of immutable data objects.
- `DataObject::collection()` for creating typed `DataCollection` instances.
- `DataCollection` with `toArray()`, `map()`, `filter()`, `count()`, `all()`, and iterable support.
- Automatic scalar type casting (int, float, string, bool).
- Backed enum casting (string and integer enums).
- Carbon date casting with configurable date format.
- Nested `DataObject` casting from arrays.
- Custom cast support via `Castable` interface and `casts()` method.
- Optional validation integration via `rules()` method using Laravel's validator.
- `ValidationException` with `errors()`, `validator()`, and first error message.
- Strict mode configuration to reject unknown input keys.
- `EncapsulaServiceProvider` with config merging and publishing.
- Package auto-discovery for Laravel.
- Publishable configuration file (`config/encapsula.php`).
- PHPUnit test suite with 48 tests and 81 assertions.
- Larastan static analysis at level 6.
- Laravel Pint code style with `laravel` preset.
- GitHub Actions CI with PHP 8.2/8.3/8.4 × Laravel 10/11/12 matrix.
- Complete README with installation, usage, API reference, and troubleshooting.
- CONTRIBUTING.md with development guidelines.

[0.1.0]: https://github.com/shudhuiami/Encapsula/releases/tag/v0.1.0
