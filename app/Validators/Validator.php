<?php

declare(strict_types=1);

namespace App\Validators;

final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    public function required(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errors[$field][] = "{$label} es obligatorio.";
        }

        return $this;
    }

    public function maxLength(string $field, int $max, string $label): self
    {
        $value = $this->value($field);
        if (is_string($value) && mb_strlen($value) > $max) {
            $this->errors[$field][] = "{$label} no debe superar {$max} caracteres.";
        }

        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        $value = $this->value($field);
        if (is_string($value) && $value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field][] = "{$label} debe tener al menos {$min} caracteres.";
        }

        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->value($field);
        if (is_string($value) && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field][] = "{$label} no tiene un formato de correo valido.";
        }

        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "{$label} tiene un valor no permitido.";
        }

        return $this;
    }

    public function positiveInteger(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && (!is_numeric($value) || (int) $value <= 0)) {
            $this->errors[$field][] = "{$label} debe ser un numero entero positivo.";
        }

        return $this;
    }

    public function date(string $field, string $label): self
    {
        $value = $this->value($field);
        if (is_string($value) && $value !== '') {
            $parsed = \DateTime::createFromFormat('Y-m-d', $value);
            if (!$parsed || $parsed->format('Y-m-d') !== $value) {
                $this->errors[$field][] = "{$label} no es una fecha valida (use AAAA-MM-DD).";
            }
        }

        return $this;
    }

    public function url(string $field, string $label): self
    {
        $value = $this->value($field);
        if (is_string($value) && $value !== '') {
            $isValid = filter_var($value, FILTER_VALIDATE_URL) !== false
                && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'));
            if (!$isValid) {
                $this->errors[$field][] = "{$label} debe ser una URL valida con protocolo http o https.";
            }
        }

        return $this;
    }

    public function custom(string $field, callable $rule, string $message): self
    {
        if (!$rule($this->value($field))) {
            $this->errors[$field][] = $message;
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstErrorsFlat(): array
    {
        $flat = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $flat[] = $error;
            }
        }

        return $flat;
    }
}
