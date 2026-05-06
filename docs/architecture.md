# Encapsula — Package Scope and Architecture

This document defines the scope, purpose, naming conventions, feature plan, and architecture for the Encapsula package. It serves as the reference for all subsequent implementation phases.

---

## 1. Package Purpose

Encapsula is a data encapsulation package for Laravel. It provides a clean, type-safe way to define structured data objects (DTOs) that can validate, cast, and transform data flowing through a Laravel application.

Modern Laravel applications pass data between layers — controllers, services, jobs, events, API responses — often using raw arrays or loosely typed structures. Encapsula replaces those patterns with explicit, self-documenting data objects that enforce structure at runtime.

## 2. Target Users

- Laravel developers building medium-to-large applications who need predictable data structures between layers.
- Teams that want lightweight DTOs without adopting a full data-mapping framework.
- Package authors who need a simple foundation for structured input/output in their own packages.

## 3. Package Type

**Laravel-focused Composer package.**

Encapsula depends on `illuminate/support` and integrates via a Laravel service provider. It can technically be used outside Laravel, but first-class support targets Laravel 10, 11, and 12.

## 4. Package Identity

| Field | Value |
|---|---|
| Composer name | `zobayer/encapsula` |
| PHP namespace | `Zobayer\Encapsula` |
| Service provider | `Zobayer\Encapsula\EncapsulaServiceProvider` |
| Facade | `Zobayer\Encapsula\Facades\Encapsula` |
| Config file | `config/encapsula.php` |
| License | MIT |
| Minimum PHP | 8.2 |
| Minimum Laravel | 10.0 |

## 5. Core Features

These are the capabilities planned for the initial release:

1. **Base data object class** — An abstract `DataObject` class that developers extend to define typed properties representing a data structure.
2. **Factory construction** — Static `from()` method accepting arrays, request objects, Eloquent models, or JSON strings and returning a hydrated data object instance.
3. **Property casting** — Automatic casting of input values to declared property types (scalars, enums, Carbon dates, nested data objects).
4. **Validation integration** — Optional validation rules defined on the data object, executed during construction, throwing a clear exception on failure.
5. **Transformation / serialization** — A `toArray()` method that converts the data object back to a plain array, respecting visibility and custom transformers.
6. **Immutability support** — Readonly properties by default; a `clone()` helper to derive modified copies.
7. **Collection support** — A typed `DataCollection` wrapper for lists of data objects with map/filter/toArray helpers.
8. **Configuration** — A publishable config file controlling default behaviors (strict mode, date format, etc.).

## 6. Non-Goals

The following are explicitly out of scope:

- **ORM / persistence** — Encapsula does not replace Eloquent. It does not manage database tables, migrations, or query building.
- **Full validation framework** — Validation is a convenience layer on top of Laravel's validator; Encapsula does not replace `illuminate/validation`.
- **API resource replacement** — Encapsula data objects can feed API resources but do not replace `JsonResource`.
- **Code generation / scaffolding CLI** — No Artisan commands for generating data objects in the initial release.
- **Event sourcing / CQRS** — Encapsula is not an event-sourcing framework.
- **Generic PHP DTO library** — While the core class can work without Laravel, non-Laravel usage is not a supported use case.

## 7. Public API Style

The public API favors **static factory methods** and **fluent, minimal interfaces**.

### Creating a data object

```php
use Zobayer\Encapsula\DataObject;

class CreateUserData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}
}

// From an array
$data = CreateUserData::from([
    'name' => 'Ahmed',
    'email' => 'ahmed@example.com',
]);

// From a Laravel request
$data = CreateUserData::from($request);

// Back to array
$data->toArray(); // ['name' => 'Ahmed', 'email' => 'ahmed@example.com', 'phone' => null]
```

### Validation

```php
class CreateUserData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }
}

// Throws Zobayer\Encapsula\Exceptions\ValidationException on invalid input
$data = CreateUserData::from($request);
```

### Collections

```php
use Zobayer\Encapsula\DataCollection;

$users = CreateUserData::collection([
    ['name' => 'Ahmed', 'email' => 'a@example.com'],
    ['name' => 'Sara', 'email' => 's@example.com'],
]);

$users->toArray(); // array of plain arrays
```

## 8. Architecture Overview

### Directory structure

```
encapsula/
├── config/
│   └── encapsula.php            # Publishable configuration
├── src/
│   ├── Concerns/                # Reusable traits
│   │   ├── HasCasting.php       # Property type casting logic
│   │   ├── HasFactory.php       # Static from() construction logic
│   │   ├── HasTransformation.php # toArray() and serialization
│   │   └── HasValidation.php    # Optional validation integration
│   ├── Contracts/               # Interfaces
│   │   └── Castable.php         # Contract for custom cast classes
│   ├── Exceptions/
│   │   └── ValidationException.php
│   ├── Facades/
│   │   └── Encapsula.php        # Optional facade
│   ├── DataCollection.php       # Typed collection of data objects
│   ├── DataObject.php           # Abstract base class
│   └── EncapsulaServiceProvider.php
├── tests/
│   ├── Unit/
│   │   ├── DataObjectTest.php
│   │   ├── DataCollectionTest.php
│   │   ├── CastingTest.php
│   │   └── ValidationTest.php
│   ├── Feature/
│   │   ├── ServiceProviderTest.php
│   │   └── ConfigTest.php
│   └── TestCase.php             # Base test case with Orchestra Testbench
├── composer.json
├── phpunit.xml
├── LICENSE
├── README.md
└── AGENTS.md
```

### Key components

| Component | Responsibility |
|---|---|
| `DataObject` | Abstract base class. Holds typed constructor properties. Provides `from()`, `toArray()`, `clone()`, `collection()`. Delegates casting, validation, and transformation to traits. |
| `HasFactory` | Trait on `DataObject`. Implements `from()` to accept arrays, Request objects, Model instances, and JSON strings. Normalizes input to an associative array before construction. |
| `HasCasting` | Trait on `DataObject`. Reads declared property types via reflection and casts input values (e.g., string→int, string→Carbon, array→nested DataObject). |
| `HasValidation` | Trait on `DataObject`. If a static `rules()` method is defined, runs input through Laravel's validator before construction. Throws `ValidationException` on failure. |
| `HasTransformation` | Trait on `DataObject`. Implements `toArray()` by iterating public properties, recursively converting nested data objects and collections. |
| `DataCollection` | Generic typed collection wrapping an array of `DataObject` instances. Provides `toArray()`, `map()`, `filter()`, `count()`, and is iterable/countable. |
| `Castable` | Interface for custom cast classes that convert raw input to a target type. |
| `EncapsulaServiceProvider` | Registers the package config. Publishes `config/encapsula.php`. No additional bindings unless the facade is used. |
| `config/encapsula.php` | Controls defaults: strict mode (throw on unknown keys), default date format, whether validation is enabled by default. |

### Data flow

```
Input (array / Request / Model / JSON)
  │
  ▼
DataObject::from()          ← HasFactory trait
  │
  ├─ normalize to array
  ├─ validate (if rules exist) ← HasValidation trait
  ├─ cast values to types       ← HasCasting trait
  │
  ▼
new DataObject(...)         ← Constructor with typed readonly properties
  │
  ▼
$dataObject->toArray()      ← HasTransformation trait
  │
  ▼
Plain array output
```

## 9. Configuration

The publishable config file (`config/encapsula.php`) will contain:

```php
return [
    // When true, unknown keys in input arrays throw an exception.
    'strict' => false,

    // Default date format used when casting date strings.
    'date_format' => 'Y-m-d H:i:s',

    // When true, validation rules are applied automatically on from().
    'validate_by_default' => true,
];
```

## 10. Development Phases

| Phase | Issue | Scope |
|---|---|---|
| 1 | #1 | Define package scope and architecture (this document) |
| 2 | #2 | Initialize Composer package structure, PSR-4 autoloading, base directories |
| 3 | #3 | Add `EncapsulaServiceProvider`, config publishing, auto-discovery |
| 4 | #4 | Implement `DataObject`, traits, `DataCollection`, casting, validation |
| 5 | #5 | Add PHPUnit tests, PHPStan, Laravel Pint |
| 6 | #6 | Write README, usage examples, troubleshooting docs |
| 7 | #7 | Add GitHub Actions CI workflow |
| 8 | #8 | Prepare first release, changelog, Packagist submission |

## 11. Dependencies

### Runtime

| Package | Purpose |
|---|---|
| `illuminate/support` ^10.0\|^11.0\|^12.0 | Laravel collections, config, service provider base |
| `illuminate/validation` ^10.0\|^11.0\|^12.0 | Optional validation integration |

### Development

| Package | Purpose |
|---|---|
| `phpunit/phpunit` ^10.5\|^11.0 | Test framework |
| `orchestra/testbench` ^8.0\|^9.0\|^10.0 | Laravel package testing |
| `larastan/larastan` ^2.0\|^3.0 | Static analysis |
| `laravel/pint` ^1.0 | Code style |
