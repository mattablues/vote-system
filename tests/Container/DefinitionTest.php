<?php

declare(strict_types=1);

namespace Radix\Tests\Container;

use PHPUnit\Framework\TestCase;
use Radix\Container\Definition;
use InvalidArgumentException;
use OutOfBoundsException;

class DefinitionTest extends TestCase
{
    public function testAddArgumentValidValue(): void
    {
        $definition = new Definition('SomeClass');
        $result = $definition->addArgument('value');

        $this->assertSame($definition, $result); // Verifiera att addArgument returnerar samma instans
    }

    public function testAddArgumentNullThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument value cannot be null.');

        $definition = new Definition('SomeClass');
        $definition->addArgument(null);
    }

    public function testAddMultipleArguments(): void
    {
        $definition = new Definition('SomeClass');
        $definition->addArgument('arg1');
        $definition->addArgument('arg2');

        $this->assertSame(['arg1', 'arg2'], $definition->getArguments());
    }

    public function testGetArgumentByIndex(): void
    {
        $definition = new Definition('SomeClass');
        $definition->addArgument('arg1');
        $definition->addArgument('arg2');

        $this->assertSame('arg1', $definition->getArgument(0));
        $this->assertSame('arg2', $definition->getArgument(1));
    }

    public function testGetArgumentInvalidIndexThrowsException(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Argument at index "10" does not exist.');

        $definition = new Definition('SomeClass');
        $definition->getArgument(10);
    }
}