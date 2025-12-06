<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\InputFilter;
use PHPUnit\Framework\TestCase;

final class InputFilterTest extends TestCase
{
    public function testStringTrimsAndLimits(): void
    {
        $result = InputFilter::string("  Hello\tWorld  ", 5);
        self::assertSame('Hello', $result);
    }

    public function testStringWithoutTrimPreservesSpaces(): void
    {
        $result = InputFilter::string("  padded  ", 20, false, false);
        self::assertSame('  padded  ', $result);
    }

    public function testLowercaseNormalisesCase(): void
    {
        $result = InputFilter::lowercase(' Admin@EXAMPLE ');
        self::assertSame('admin@example', $result);
    }

    public function testEmailValidation(): void
    {
        self::assertSame('user@example.com', InputFilter::email(' User@Example.COM '));
        self::assertNull(InputFilter::email('not-an-email'));
    }

    /**
     * @return array<int, array{input:mixed, expected:bool}>
     */
    public static function booleanValuesProvider(): array
    {
        return [
            ['1', true],
            ['yes', true],
            ['on', true],
            ['true', true],
            [1, true],
            ['0', false],
            ['false', false],
            [0, false],
            [null, false],
        ];
    }

    /**
     * @dataProvider booleanValuesProvider
     * @param mixed $input
     */
    public function testBoolNormalization($input, bool $expected): void
    {
        self::assertSame($expected, InputFilter::bool($input));
    }

    public function testDigitsExtraction(): void
    {
        self::assertSame('123456', InputFilter::digits(' 12-34 56 '));
        self::assertNull(InputFilter::digits('abc'));
    }

    public function testMultilineStripsControlChars(): void
    {
        $text = "line1\r\nline2\x00line3";
        self::assertSame("line1\nline2line3", InputFilter::multiline($text));
    }

    public function testArrayOfStringsFiltersEmptyEntries(): void
    {
        $values = ['  foo ', '', '   ', "bar\n", 123];
        self::assertSame(['foo', 'bar', '123'], InputFilter::arrayOfStrings($values));
    }

    public function testIntClamping(): void
    {
        self::assertSame(10, InputFilter::int('10', 0, 20));
        self::assertSame(0, InputFilter::int('-5', 0, 20));
        self::assertSame(20, InputFilter::int(999, 0, 20));
    }
}
