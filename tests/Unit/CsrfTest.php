<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Csrf;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testInjectIntoFormsAddsHiddenField(): void
    {
        $markup = '<form method="post"><input type="text" name="foo"></form>';
        $result = Csrf::injectIntoForms($markup);

        self::assertStringContainsString('data-csrf-protected="1"', $result);
        self::assertStringContainsString('name="_token"', $result);
    }

    #[RunInSeparateProcess]
    public function testValidateRequestMatchesSessionToken(): void
    {
        $token = Csrf::token();
        $_POST['_token'] = $token;

        self::assertTrue(Csrf::validateRequest());
    }
}
