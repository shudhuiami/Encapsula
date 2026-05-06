<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Unit;

use Carbon\Carbon;
use Zobayer\Encapsula\Contracts\Castable;
use Zobayer\Encapsula\DataObject;
use Zobayer\Encapsula\Tests\TestCase;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum Priority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}

class UpperCaseCast implements Castable
{
    public function cast(mixed $value): string
    {
        return strtoupper((string) $value);
    }
}

class EnumData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly UserStatus $status,
    ) {}
}

class IntEnumData extends DataObject
{
    public function __construct(
        public readonly string $label,
        public readonly Priority $priority,
    ) {}
}

class DateData extends DataObject
{
    public function __construct(
        public readonly string $label,
        public readonly Carbon $created_at,
    ) {}
}

class WithCastsData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $title,
    ) {}

    /**
     * Define custom casts for properties.
     *
     * @return array<string, class-string<Castable>>
     */
    public static function casts(): array
    {
        return [
            'title' => UpperCaseCast::class,
        ];
    }
}

class NullableData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $age = null,
        public readonly ?UserStatus $status = null,
    ) {}
}

class CastingTest extends TestCase
{
    public function test_string_enum_casting_from_string(): void
    {
        $data = EnumData::from([
            'name' => 'Ahmed',
            'status' => 'active',
        ]);

        $this->assertSame(UserStatus::Active, $data->status);
    }

    public function test_string_enum_casting_from_enum_instance(): void
    {
        $data = EnumData::from([
            'name' => 'Ahmed',
            'status' => UserStatus::Inactive,
        ]);

        $this->assertSame(UserStatus::Inactive, $data->status);
    }

    public function test_int_enum_casting(): void
    {
        $data = IntEnumData::from([
            'label' => 'Task',
            'priority' => 3,
        ]);

        $this->assertSame(Priority::High, $data->priority);
    }

    public function test_enum_to_array_returns_value(): void
    {
        $data = EnumData::from([
            'name' => 'Ahmed',
            'status' => 'active',
        ]);

        $array = $data->toArray();

        $this->assertSame('active', $array['status']);
    }

    public function test_carbon_casting_from_string(): void
    {
        $data = DateData::from([
            'label' => 'Event',
            'created_at' => '2026-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $data->created_at);
        $this->assertSame('2026-01-15 10:30:00', $data->created_at->format('Y-m-d H:i:s'));
    }

    public function test_carbon_casting_from_carbon_instance(): void
    {
        $carbon = Carbon::parse('2026-06-01 12:00:00');

        $data = DateData::from([
            'label' => 'Event',
            'created_at' => $carbon,
        ]);

        $this->assertSame($carbon, $data->created_at);
    }

    public function test_carbon_to_array_uses_config_format(): void
    {
        $data = DateData::from([
            'label' => 'Event',
            'created_at' => '2026-01-15 10:30:00',
        ]);

        $array = $data->toArray();

        $this->assertSame('2026-01-15 10:30:00', $array['created_at']);
    }

    public function test_carbon_to_array_respects_custom_format(): void
    {
        $this->app['config']->set('encapsula.date_format', 'Y-m-d');

        $carbon = Carbon::parse('2026-01-15 10:30:00');

        $data = DateData::from([
            'label' => 'Event',
            'created_at' => $carbon,
        ]);

        $array = $data->toArray();

        $this->assertSame('2026-01-15', $array['created_at']);
    }

    public function test_custom_castable_class(): void
    {
        $data = WithCastsData::from([
            'name' => 'Ahmed',
            'title' => 'hello world',
        ]);

        $this->assertSame('HELLO WORLD', $data->title);
    }

    public function test_custom_castable_preserves_other_casts(): void
    {
        $data = WithCastsData::from([
            'name' => 123,
            'title' => 'hello',
        ]);

        $this->assertSame('123', $data->name);
        $this->assertSame('HELLO', $data->title);
    }

    public function test_nullable_values_pass_through(): void
    {
        $data = NullableData::from([
            'name' => 'Ahmed',
            'age' => null,
            'status' => null,
        ]);

        $this->assertNull($data->age);
        $this->assertNull($data->status);
    }

    public function test_nullable_with_default_omitted(): void
    {
        $data = NullableData::from([
            'name' => 'Ahmed',
        ]);

        $this->assertNull($data->age);
        $this->assertNull($data->status);
    }
}
