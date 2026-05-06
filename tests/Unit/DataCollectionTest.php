<?php

declare(strict_types=1);

namespace Zobayer\Encapsula\Tests\Unit;

use Zobayer\Encapsula\DataCollection;
use Zobayer\Encapsula\Tests\TestCase;

class DataCollectionTest extends TestCase
{
    public function test_collection_count(): void
    {
        $collection = SimpleData::collection([
            ['name' => 'Ahmed', 'email' => 'a@example.com'],
            ['name' => 'Sara', 'email' => 's@example.com'],
            ['name' => 'Zain', 'email' => 'z@example.com'],
        ]);

        $this->assertCount(3, $collection);
    }

    public function test_collection_to_array(): void
    {
        $collection = SimpleData::collection([
            ['name' => 'Ahmed', 'email' => 'a@example.com'],
            ['name' => 'Sara', 'email' => 's@example.com'],
        ]);

        $expected = [
            ['name' => 'Ahmed', 'email' => 'a@example.com', 'phone' => null],
            ['name' => 'Sara', 'email' => 's@example.com', 'phone' => null],
        ];

        $this->assertSame($expected, $collection->toArray());
    }

    public function test_collection_map(): void
    {
        $collection = SimpleData::collection([
            ['name' => 'Ahmed', 'email' => 'a@example.com'],
            ['name' => 'Sara', 'email' => 's@example.com'],
        ]);

        $names = $collection->map(fn (SimpleData $item) => $item->name);

        $this->assertSame(['Ahmed', 'Sara'], $names);
    }

    public function test_collection_filter(): void
    {
        $collection = SimpleData::collection([
            ['name' => 'Ahmed', 'email' => 'a@example.com'],
            ['name' => 'Sara', 'email' => 's@example.com'],
            ['name' => 'Zain', 'email' => 'z@example.com'],
        ]);

        $filtered = $collection->filter(fn (SimpleData $item) => $item->name !== 'Sara');

        $this->assertCount(2, $filtered);
        $this->assertInstanceOf(DataCollection::class, $filtered);
    }

    public function test_collection_all(): void
    {
        $collection = SimpleData::collection([
            ['name' => 'Ahmed', 'email' => 'a@example.com'],
        ]);

        $all = $collection->all();

        $this->assertCount(1, $all);
        $this->assertInstanceOf(SimpleData::class, $all[0]);
    }

    public function test_collection_is_iterable(): void
    {
        $collection = SimpleData::collection([
            ['name' => 'Ahmed', 'email' => 'a@example.com'],
            ['name' => 'Sara', 'email' => 's@example.com'],
        ]);

        $names = [];
        foreach ($collection as $item) {
            $names[] = $item->name;
        }

        $this->assertSame(['Ahmed', 'Sara'], $names);
    }

    public function test_empty_collection(): void
    {
        $collection = SimpleData::collection([]);

        $this->assertCount(0, $collection);
        $this->assertSame([], $collection->toArray());
    }
}
