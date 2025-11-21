<?php

declare(strict_types=1);

namespace Radix\Tests\Collection;

use PHPUnit\Framework\TestCase;
use Radix\Collection\Collection;

class CollectionTest extends TestCase
{
    public function testBasicArrayAccessAndCount(): void
    {
        $c = new Collection([1, 2, 3]);
        $this->assertCount(3, $c);
        $this->assertSame(1, $c[0]);
        $c[] = 4;
        $this->assertSame(4, $c[3]);
        unset($c[1]);
        $this->assertFalse(isset($c[1]));
        $this->assertCount(3, $c);
    }

    public function testGetSetAndRemove(): void
    {
        $c = new Collection(['a' => 1]);
        $this->assertSame(1, $c->get('a'));
        $this->assertNull($c->get('missing'));
        $c->set('b', 2);
        $this->assertSame(2, $c->get('b'));
        $removed = $c->remove('a');
        $this->assertSame(1, $removed);
        $this->assertNull($c->get('a'));
    }

    public function testFirstLastAndFirstWhere(): void
    {
        $c = new Collection([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $this->assertSame(['id' => 1, 'name' => 'Alice'], $c->first());
        $this->assertSame(['id' => 2, 'name' => 'Bob'], $c->last());
        $this->assertSame(['id' => 2, 'name' => 'Bob'], $c->firstWhere('name', 'Bob'));
        $this->assertNull($c->firstWhere('name', 'Eve'));
    }

    public function testMapFilterRejectReduce(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        // map: vi vet att kollektionen innehåller ints, så vi kan typa v som int
        $mapped = $c->map(fn(int $v, int|string $k): int => $v * 2);
        $this->assertSame([2, 4, 6, 8], $mapped->toArray());

        // filter: officiell signatur är callable(mixed, int|string): bool,
        // så vi tar mixed, men smalnar av med runtime‑check.
        $filtered = $c->filter(function (mixed $v, int|string $k): bool {
            if (!is_int($v)) {
                $this->fail('Expected int value in Collection::filter() test.');
            }
            return $v % 2 === 0;
        });
        $this->assertSame([1 => 2, 3 => 4], $filtered->toArray());

        $rejected = $c->reject(function (mixed $v, int|string $k): bool {
            if (!is_int($v)) {
                $this->fail('Expected int value in Collection::reject() test.');
            }
            return $v <= 2;
        });
        $this->assertSame([2 => 3, 3 => 4], $rejected->toArray());

        $sum = $c->reduce(function (mixed $acc, mixed $v, int|string $k): int {
            if (!is_int($acc)) {
                $this->fail('Expected int accumulator in Collection::reduce() test.');
            }
            if (!is_int($v)) {
                $this->fail('Expected int value in Collection::reduce() test.');
            }
            return $acc + $v;
        }, 0);
        $this->assertSame(10, $sum);
    }

    public function testOnlyExceptUniqueValuesKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 1, 'c' => 2]);

        $only = $c->only(['a', 'c']);
        $this->assertSame(['a' => 1, 'c' => 2], $only->toArray());

        $except = $c->except(['b']);
        $this->assertSame(['a' => 1, 'c' => 2], $except->toArray());

        $unique = $c->unique();
        $this->assertSame(['a' => 1, 'c' => 2], $unique->toArray());

        $vals = $c->values();
        $this->assertSame([1,1,2], $vals->toArray());

        $keys = $c->keys();
        $this->assertSame(['a','b','c'], $keys->toArray());
    }

    public function testPluckOnArraysAndObjects(): void
    {
        $obj = (object) ['id' => 2, 'name' => 'Bob'];
        $c = new Collection([
            ['id' => 1, 'name' => 'Alice'],
            $obj,
        ]);

        $names = $c->pluck('name')->values()->toArray();
        $this->assertSame(['Alice', 'Bob'], $names);

        $namesById = $c->pluck('name', 'id')->toArray();
        $this->assertSame([1 => 'Alice', 2 => 'Bob'], $namesById);
    }

    public function testClearAndIsEmpty(): void
    {
        $c = new Collection([1]);
        $this->assertFalse($c->isEmpty());
        $c->clear();
        $this->assertTrue($c->isEmpty());
    }
}
