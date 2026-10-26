<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_token_is_generated_and_persisted_in_session(): void
    {
        $token = Csrf::token();

        $this->assertNotEmpty($token);
        $this->assertSame($token, Csrf::token());
    }

    public function test_verify_accepts_matching_token(): void
    {
        $token = Csrf::token();

        $this->assertTrue(Csrf::verify($token));
    }

    public function test_verify_rejects_invalid_token(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify('token-invalido'));
    }

    public function test_verify_rejects_empty_token(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify(''));
        $this->assertFalse(Csrf::verify(null));
    }

    public function test_rotate_changes_the_token(): void
    {
        $original = Csrf::token();
        Csrf::rotate();

        $this->assertNotSame($original, Csrf::token());
    }
}
