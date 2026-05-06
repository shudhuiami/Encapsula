# Encapsula

A clean, type-safe data encapsulation (DTO) package for Laravel.

Encapsula provides structured data objects that validate, cast, and transform data flowing through your Laravel application. Replace raw arrays and loosely typed structures with explicit, self-documenting DTOs.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

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

This creates `config/encapsula.php` with default settings.

## Quick Start

### Define a Data Object

Extend `DataObject` and use constructor-promoted properties:

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
```

### Create from Various Sources

```php
// From an array
$user = CreateUserData::from([
    'name' => 'Ahmed',
    'email' => 'ahmed@example.com',
]);

// From a Laravel request
$user = CreateUserData::from($request);

// From an Eloquent model
$user = CreateUserData::from($model);

// From a JSON string
$user = CreateUserData::from('{"name":"Ahmed","email":"ahmed@example.com"}');
```

### Convert Back to Array

```php
$user->toArray();
// ['name' => 'Ahmed', 'email' => 'ahmed@example.com', 'phone' => null]
```

### Create Modified Copies

Since properties are readonly, use `clone()` to derive new instances:

```php
$updated = $user->clone(['email' => 'new@example.com']);
// $updated->email === 'new@example.com'
// $user->email === 'ahmed@example.com' (unchanged)
```

## Validation

Define a static `rules()` method to validate input automatically:

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
```

Invalid input throws `Zobayer\Encapsula\Exceptions\ValidationException`:

```php
try {
    $user = CreateUserData::from(['name' => '', 'email' => 'bad']);
} catch (\Zobayer\Encapsula\Exceptions\ValidationException $e) {
    $e->errors();    // ['name' => [...], 'email' => [...]]
    $e->validator(); // Laravel Validator instance
    $e->getMessage(); // First error message
}
```

Validation can be disabled globally via config:

```php
// config/encapsula.php
'validate_by_default' => false,
```

## Type Casting

Properties are automatically cast based on their declared types.

### Scalar Casting

```php
class ProductData extends DataObject
{
    public function __construct(
        public readonly int $quantity,
        public readonly float $price,
        public readonly bool $active,
        public readonly string $name,
    ) {}
}

// String inputs are cast to declared types
$product = ProductData::from([
    'quantity' => '5',     // cast to int 5
    'price' => '19.99',   // cast to float 19.99
    'active' => '1',      // cast to bool true
    'name' => 123,         // cast to string '123'
]);
```

### Enum Casting

```php
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

class UserData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly Status $status,
    ) {}
}

$user = UserData::from(['name' => 'Ahmed', 'status' => 'active']);
// $user->status === Status::Active
```

### Carbon Date Casting

```php
use Carbon\Carbon;

class EventData extends DataObject
{
    public function __construct(
        public readonly string $title,
        public readonly Carbon $starts_at,
    ) {}
}

$event = EventData::from(['title' => 'Launch', 'starts_at' => '2026-01-15 10:30:00']);
// $event->starts_at is a Carbon instance
```

The date format is configurable:

```php
// config/encapsula.php
'date_format' => 'Y-m-d H:i:s', // default
```

### Nested Data Objects

```php
class AddressData extends DataObject
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class PersonData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly AddressData $address,
    ) {}
}

$person = PersonData::from([
    'name' => 'Ahmed',
    'address' => ['street' => '123 Main St', 'city' => 'Dhaka'],
]);
// $person->address is an AddressData instance
```

### Custom Casts

Implement `Castable` for custom type conversions, then map them in a `casts()` method:

```php
use Zobayer\Encapsula\Contracts\Castable;

class UpperCaseCast implements Castable
{
    public function cast(mixed $value): string
    {
        return strtoupper((string) $value);
    }
}

class TagData extends DataObject
{
    public function __construct(
        public readonly string $label,
    ) {}

    public static function casts(): array
    {
        return [
            'label' => UpperCaseCast::class,
        ];
    }
}

$tag = TagData::from(['label' => 'hello']);
// $tag->label === 'HELLO'
```

## Collections

Create typed collections from arrays of data:

```php
$users = CreateUserData::collection([
    ['name' => 'Ahmed', 'email' => 'ahmed@example.com'],
    ['name' => 'Sara', 'email' => 'sara@example.com'],
]);

$users->toArray();  // array of plain arrays
$users->count();    // 2
$users->all();      // array of CreateUserData instances

// Map to extract values
$names = $users->map(fn (CreateUserData $u) => $u->name);
// ['Ahmed', 'Sara']

// Filter
$filtered = $users->filter(fn (CreateUserData $u) => $u->name === 'Ahmed');
// DataCollection with 1 item

// Iterate
foreach ($users as $user) {
    echo $user->name;
}
```

## Configuration

After publishing (`php artisan vendor:publish --tag=encapsula-config`), edit `config/encapsula.php`:

| Option | Type | Default | Description |
|---|---|---|---|
| `strict` | `bool` | `false` | When `true`, unknown keys in input throw `InvalidArgumentException`. When `false`, unknown keys are silently ignored. |
| `date_format` | `string` | `'Y-m-d H:i:s'` | Format used when casting date strings to Carbon and when serializing dates in `toArray()`. |
| `validate_by_default` | `bool` | `true` | When `true`, validation rules defined via `rules()` are applied automatically on `from()`. Set to `false` to skip validation globally. |

## API Reference

### DataObject (abstract)

| Method | Signature | Description |
|---|---|---|
| `from()` | `static from(array\|Request\|Model\|string $source): static` | Create an instance from an array, Laravel Request, Eloquent Model, or JSON string. |
| `toArray()` | `toArray(): array` | Convert the data object to a plain associative array. Nested DataObjects and enums are recursively converted. |
| `clone()` | `clone(array $overrides = []): static` | Create a modified copy with the given property overrides. |
| `collection()` | `static collection(array $items): DataCollection` | Create a typed DataCollection from an array of arrays. |

### DataCollection

| Method | Signature | Description |
|---|---|---|
| `toArray()` | `toArray(): array` | Convert all items to plain arrays. |
| `map()` | `map(callable $callback): array` | Apply a callback to each item and return results. |
| `filter()` | `filter(callable $callback): static` | Filter items and return a new DataCollection. |
| `count()` | `count(): int` | Get the number of items. |
| `all()` | `all(): array` | Get all DataObject instances as a plain array. |

### ValidationException

| Method | Signature | Description |
|---|---|---|
| `errors()` | `errors(): array` | Get validation errors grouped by field. |
| `validator()` | `validator(): ?Validator` | Get the underlying Laravel Validator instance. |
| `getMessage()` | `getMessage(): string` | Get the first validation error message. |

### Castable (interface)

| Method | Signature | Description |
|---|---|---|
| `cast()` | `cast(mixed $value): mixed` | Cast the given raw value to the target type. |

## Troubleshooting

### Unknown named parameter error

If you see `Unknown named parameter $some_key`, enable strict mode to identify extra keys:

```php
// config/encapsula.php
'strict' => true,
```

In non-strict mode (default), unknown keys are silently stripped.

### Validation not running

Check that `validate_by_default` is `true` in your config and that your DataObject defines a static `rules()` method.

### Carbon parse errors

Ensure date strings match the configured `date_format`. The default is `Y-m-d H:i:s`. If your dates use a different format, update the config.

### Service provider not loading

If auto-discovery is disabled, register the provider manually in `config/app.php`:

```php
Zobayer\Encapsula\EncapsulaServiceProvider::class,
```

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
