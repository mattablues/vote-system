<?php

declare(strict_types=1);

namespace Radix\Tests\Container\Argument\Literal;

use PHPUnit\Framework\TestCase;
use Radix\Container\Argument\Literal\CallableArgument;
use Radix\Container\Exception\ContainerInvalidArgumentException;

class CallableArgumentTest extends TestCase
{
    public function testConstructorValidCallable(): void
    {
        $callable = new CallableArgument('strlen');
        $this->assertIsCallable($callable->getValue());
    }

    public function testConstructorInvalidCallableThrowsException(): void
    {
        $this->expectException(ContainerInvalidArgumentException::class);
        $this->expectExceptionMessage('Value is not a valid callable.');

        new CallableArgument('not_callable'); // Ogiltigt värde
    }

    public function testInvoke(): void
    {
        $callable = new CallableArgument('strtoupper'); // Callable
        $result = $callable->invoke('test'); // Anropa callable

        $this->assertSame('TEST', $result);
    }

    public function testIsMethod(): void
    {
        $object = new class {
            public function method(): void {}
        };

        $callable = new CallableArgument([$object, 'method']);
        $this->assertTrue($callable->isMethod());
    }

    public function testDescribe(): void
    {
        $callable = new CallableArgument('strlen');
        $this->assertSame('Callable function: strlen', $callable->describe());
    }
}
