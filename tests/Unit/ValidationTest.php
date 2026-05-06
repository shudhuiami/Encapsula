<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Unit;

use Zobayer\Encapsula\DataObject;
use Zobayer\Encapsula\Exceptions\ValidationException;
use Zobayer\Encapsula\Tests\TestCase;

class StrictValidatedData extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'email' => ['required', 'email'],
        ];
    }
}

class NoRulesData extends DataObject
{
    public function __construct(
        public readonly string $name,
    ) {}
}

class ValidationTest extends TestCase
{
    public function test_valid_data_passes(): void
    {
        $data = StrictValidatedData::from([
            'name' => 'Ahmed',
            'age' => 30,
            'email' => 'ahmed@example.com',
        ]);

        $this->assertSame('Ahmed', $data->name);
        $this->assertSame(30, $data->age);
    }

    public function test_missing_required_field_throws(): void
    {
        $this->expectException(ValidationException::class);

        StrictValidatedData::from([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);
    }

    public function test_invalid_email_throws(): void
    {
        $this->expectException(ValidationException::class);

        StrictValidatedData::from([
            'name' => 'Ahmed',
            'age' => 30,
            'email' => 'not-an-email',
        ]);
    }

    public function test_exception_message_contains_first_error(): void
    {
        try {
            StrictValidatedData::from([
                'name' => 'A',
                'age' => -1,
                'email' => 'bad',
            ]);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_exception_errors_contain_all_fields(): void
    {
        try {
            StrictValidatedData::from([
                'name' => '',
                'age' => 200,
                'email' => 'bad',
            ]);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('age', $errors);
            $this->assertArrayHasKey('email', $errors);
        }
    }

    public function test_exception_has_validator_instance(): void
    {
        try {
            StrictValidatedData::from([
                'name' => '',
                'age' => 0,
                'email' => 'bad',
            ]);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertNotNull($e->validator());
        }
    }

    public function test_data_without_rules_skips_validation(): void
    {
        $data = NoRulesData::from(['name' => '']);

        $this->assertSame('', $data->name);
    }

    public function test_validate_by_default_false_skips_validation(): void
    {
        $this->app['config']->set('encapsula.validate_by_default', false);

        $data = StrictValidatedData::from([
            'name' => '',
            'age' => -999,
            'email' => 'not-valid',
        ]);

        $this->assertSame('', $data->name);
    }

    public function test_validate_by_default_true_enforces_rules(): void
    {
        $this->app['config']->set('encapsula.validate_by_default', true);

        $this->expectException(ValidationException::class);

        StrictValidatedData::from([
            'name' => '',
            'age' => -1,
            'email' => 'bad',
        ]);
    }
}
