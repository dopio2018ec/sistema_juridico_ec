<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validators\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_field_fails_when_empty(): void
    {
        $validator = Validator::make(['nombre' => ''])->required('nombre', 'El nombre');

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('nombre', $validator->errors());
    }

    public function test_required_field_passes_when_present(): void
    {
        $validator = Validator::make(['nombre' => 'Juan'])->required('nombre', 'El nombre');

        $this->assertFalse($validator->fails());
    }

    public function test_email_rule_rejects_invalid_format(): void
    {
        $validator = Validator::make(['correo' => 'no-es-un-correo'])->email('correo', 'El correo');

        $this->assertTrue($validator->fails());
    }

    public function test_email_rule_accepts_valid_format(): void
    {
        $validator = Validator::make(['correo' => 'usuario@dominio.ec'])->email('correo', 'El correo');

        $this->assertFalse($validator->fails());
    }

    public function test_max_length_rule_rejects_long_values(): void
    {
        $validator = Validator::make(['titulo' => str_repeat('a', 10)])->maxLength('titulo', 5, 'El titulo');

        $this->assertTrue($validator->fails());
    }

    public function test_min_length_rule_rejects_short_password(): void
    {
        $validator = Validator::make(['password' => '123'])->minLength('password', 8, 'La contrasena');

        $this->assertTrue($validator->fails());
    }

    public function test_date_rule_rejects_invalid_date(): void
    {
        $validator = Validator::make(['fecha' => '2024-13-40'])->date('fecha', 'La fecha');

        $this->assertTrue($validator->fails());
    }

    public function test_date_rule_accepts_valid_date(): void
    {
        $validator = Validator::make(['fecha' => '2024-05-20'])->date('fecha', 'La fecha');

        $this->assertFalse($validator->fails());
    }

    public function test_url_rule_rejects_non_http_scheme(): void
    {
        $validator = Validator::make(['url' => 'ftp://example.com'])->url('url', 'La URL');

        $this->assertTrue($validator->fails());
    }

    public function test_url_rule_accepts_https(): void
    {
        $validator = Validator::make(['url' => 'https://www.registroficial.gob.ec/doc'])->url('url', 'La URL');

        $this->assertFalse($validator->fails());
    }

    public function test_positive_integer_rule_rejects_negative_values(): void
    {
        $validator = Validator::make(['id' => '-5'])->positiveInteger('id', 'El identificador');

        $this->assertTrue($validator->fails());
    }

    public function test_in_rule_rejects_value_outside_allowed_list(): void
    {
        $validator = Validator::make(['estado' => 'INVALIDO'])->in('estado', ['VIGENTE', 'DEROGADA'], 'El estado');

        $this->assertTrue($validator->fails());
    }
}
