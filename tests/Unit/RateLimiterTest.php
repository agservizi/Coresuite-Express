<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\RateLimiter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testHitAndTooManyAttempts(): void
    {
        $key = 'rate:test';
        RateLimiter::clear($key);

        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit($key, 2);
        }

        self::assertTrue(RateLimiter::tooManyAttempts($key, 2, 2));
        self::assertGreaterThanOrEqual(1, RateLimiter::availableIn($key));
    }

    #[RunInSeparateProcess]
    public function testClearResetsAttempts(): void
    {
        $key = 'rate:clear';
        RateLimiter::hit($key, 10);
        self::assertTrue(RateLimiter::tooManyAttempts($key, 1, 10));

        RateLimiter::clear($key);
        self::assertFalse(RateLimiter::tooManyAttempts($key, 1, 10));
    }
}
