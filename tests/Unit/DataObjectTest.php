<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Unit;

use InvalidArgumentException;
use Zobayer\Encapsula\DataCollection;
use Zobayer\Encapsula\DataObject;
use Zobayer\Encapsula\Exceptions\ValidationException;
use Zobayer\Encapsula\Tests\TestCase;

class SimpleData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}
}

class ValidatedData extends DataObject
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

class NestedAddress extends DataObject
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class NestedData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly NestedAddress $address,
    ) {}
}

class CastingData extends DataObject
{
    public function __construct(
        public readonly int $age,
        public readonly float $score,
        public readonly bool $active,
        public readonly string $label,
    ) {}
}

class DataObjectTest extends TestCase
{
    public function test_from_array(): void
    {
        $data = SimpleData::from([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $this->assertInstanceOf(SimpleData::class, $data);
        $this->assertSame('Ahmed', $data->name);
        $this->assertSame('ahmed@example.com', $data->email);
        $this->assertNull($data->phone);
    }

    public function test_from_array_with_optional(): void
    {
        $data = SimpleData::from([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'phone' => '+1234567890',
        ]);

        $this->assertSame('+1234567890', $data->phone);
    }

    public function test_from_json_string(): void
    {
        $json = json_encode(['name' => 'Sara', 'email' => 'sara@example.com']);
        $data = SimpleData::from($json);

        $this->assertSame('Sara', $data->name);
        $this->assertSame('sara@example.com', $data->email);
    }

    public function test_from_invalid_json_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        SimpleData::from('not valid json');
    }

    public function test_to_array(): void
    {
        $data = SimpleData::from([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $this->assertSame([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'phone' => null,
        ], $data->toArray());
    }

    public function test_clone_creates_modified_copy(): void
    {
        $original = SimpleData::from([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $cloned = $original->clone(['name' => 'Sara']);

        $this->assertSame('Sara', $cloned->name);
        $this->assertSame('ahmed@example.com', $cloned->email);
        // Original is unchanged
        $this->assertSame('Ahmed', $original->name);
    }

    public function test_collection(): void
    {
        $collection = SimpleData::collection([
            ['name' => 'Ahmed', 'email' => 'a@example.com'],
            ['name' => 'Sara', 'email' => 's@example.com'],
        ]);

        $this->assertInstanceOf(DataCollection::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function test_scalar_casting(): void
    {
        $data = CastingData::from([
            'age' => '25',
            'score' => '9.5',
            'active' => '1',
            'label' => 123,
        ]);

        $this->assertSame(25, $data->age);
        $this->assertSame(9.5, $data->score);
        $this->assertTrue($data->active);
        $this->assertSame('123', $data->label);
    }

    public function test_nested_data_object_casting(): void
    {
        $data = NestedData::from([
            'name' => 'Ahmed',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Dhaka',
            ],
        ]);

        $this->assertInstanceOf(NestedAddress::class, $data->address);
        $this->assertSame('123 Main St', $data->address->street);
        $this->assertSame('Dhaka', $data->address->city);
    }

    public function test_nested_data_object_to_array(): void
    {
        $data = NestedData::from([
            'name' => 'Ahmed',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Dhaka',
            ],
        ]);

        $this->assertSame([
            'name' => 'Ahmed',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Dhaka',
            ],
        ], $data->toArray());
    }

    public function test_validation_passes(): void
    {
        $data = ValidatedData::from([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $this->assertSame('Ahmed', $data->name);
    }

    public function test_validation_fails_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        ValidatedData::from([
            'name' => '',
            'email' => 'not-an-email',
        ]);
    }

    public function test_validation_exception_contains_errors(): void
    {
        try {
            ValidatedData::from([
                'name' => '',
                'email' => 'not-an-email',
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertNotNull($e->validator());
        }
    }

    public function test_strict_mode_rejects_unknown_keys(): void
    {
        $this->app['config']->set('encapsula.strict', true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown keys');

        SimpleData::from([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'unknown_key' => 'value',
        ]);
    }

    public function test_non_strict_mode_ignores_unknown_keys(): void
    {
        $this->app['config']->set('encapsula.strict', false);

        $data = SimpleData::from([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'unknown_key' => 'value',
        ]);

        $this->assertSame('Ahmed', $data->name);
    }

    public function test_validation_disabled_by_config(): void
    {
        $this->app['config']->set('encapsula.validate_by_default', false);

        // This would normally fail validation but config disables it
        $data = ValidatedData::from([
            'name' => '',
            'email' => 'not-an-email',
        ]);

        $this->assertSame('', $data->name);
    }
}
