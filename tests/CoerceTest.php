<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\Coerce;
use PHPUnit\Framework\TestCase;

final class CoerceTest extends TestCase
{
    /**
     * @dataProvider provideStringTestCases
     *
     * @param mixed $val
     */
    public function testToString($val, string $expected): void
    {
        $this->assertSame($expected, Coerce::toString($val));
    }

    public function provideStringTestCases(): \Generator
    {
        yield ['foo', 'foo'];
        yield [1, '1'];
        yield [1.1, '1.1'];
        yield [true, '1'];
        yield [false, ''];
        yield [null, ''];
        yield [$this, $this->__toString()];
    }

    /**
     * @dataProvider provideInvalidStringTestCases
     *
     * @param mixed $val
     */
    public function testToStringThrowsOnUncoercableValue($val): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot coerce this value to string');

        Coerce::toString($val);
    }

    public function provideInvalidStringTestCases(): \Generator
    {
        yield [new \stdClass()];
        yield [STDOUT];
    }

    public function __toString(): string
    {
        return 'some object';
    }
}
