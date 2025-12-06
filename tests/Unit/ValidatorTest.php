<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    /**
     * @dataProvider iccidProvider
     */
    public function testIsValidIccid(bool $expected, string $candidate): void
    {
        self::assertSame($expected, Validator::isValidICCID($candidate));
    }

    /**
     * @return array<string, array{bool, string}>
     */
    public static function iccidProvider(): array
    {
        return [
            'nineteen digits' => [true, '8939101017012345678'],
            'twenty digits' => [true, '89391010170123456789'],
            'spaces stripped' => [true, '8939 1010 1701 2345 678'],
            'too short' => [false, '1234'],
            'non numeric' => [false, '89391010ABC123456789'],
            'mixed chars' => [false, ' 89 39-1010-1701-2345-60 '],
        ];
    }
}
