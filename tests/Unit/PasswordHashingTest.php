<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifica la politica de contrasenas exigida por el requerimiento:
 * nunca texto plano, siempre hash verificable con password_verify().
 */
final class PasswordHashingTest extends TestCase
{
    public function test_password_hash_is_never_the_plain_text(): void
    {
        $plain = 'ContrasenaSegura123';
        $hash = password_hash($plain, PASSWORD_DEFAULT);

        $this->assertNotSame($plain, $hash);
    }

    public function test_password_verify_accepts_correct_password(): void
    {
        $plain = 'ContrasenaSegura123';
        $hash = password_hash($plain, PASSWORD_DEFAULT);

        $this->assertTrue(password_verify($plain, $hash));
    }

    public function test_password_verify_rejects_incorrect_password(): void
    {
        $hash = password_hash('ContrasenaSegura123', PASSWORD_DEFAULT);

        $this->assertFalse(password_verify('OtraContrasena', $hash));
    }

    public function test_password_needs_rehash_is_false_for_current_algorithm(): void
    {
        $hash = password_hash('ContrasenaSegura123', PASSWORD_DEFAULT);

        $this->assertFalse(password_needs_rehash($hash, PASSWORD_DEFAULT));
    }
}
