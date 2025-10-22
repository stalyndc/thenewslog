<?php

declare(strict_types=1);

namespace App\Services;

class Validator
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    public function required(string $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            $this->errors[$field] = sprintf('%s is required.', $this->humanize($field));
            return false;
        }

        return true;
    }

    public function minLength(string $field, ?string $value, int $min): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (strlen($value) < $min) {
            $this->errors[$field] = sprintf('%s must be at least %d characters.', $this->humanize($field), $min);
            return false;
        }

        return true;
    }

    public function maxLength(string $field, ?string $value, int $max): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (strlen($value) > $max) {
            $this->errors[$field] = sprintf('%s must not exceed %d characters.', $this->humanize($field), $max);
            return false;
        }

        return true;
    }

    public function email(string $field, ?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = sprintf('%s must be a valid email address.', $this->humanize($field));
            return false;
        }

        return true;
    }

    public function url(string $field, ?string $value, bool $https = false): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $filter = $https ? FILTER_FLAG_PATH_REQUIRED : 0;

        if (filter_var($value, FILTER_VALIDATE_URL, $filter) === false) {
            $this->errors[$field] = sprintf('%s must be a valid URL.', $this->humanize($field));
            return false;
        }

        return true;
    }

    public function date(string $field, ?string $value, string $format = 'Y-m-d'): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $parsed = \DateTime::createFromFormat($format, $value);

        if ($parsed === false) {
            $this->errors[$field] = sprintf('%s must be a valid date in %s format.', $this->humanize($field), $format);
            return false;
        }

        return true;
    }

    public function integer(string $field, mixed $value, ?int $min = null, ?int $max = null): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            $this->errors[$field] = sprintf('%s must be an integer.', $this->humanize($field));
            return false;
        }

        $intValue = (int) $value;

        if ($min !== null && $intValue < $min) {
            $this->errors[$field] = sprintf('%s must be at least %d.', $this->humanize($field), $min);
            return false;
        }

        if ($max !== null && $intValue > $max) {
            $this->errors[$field] = sprintf('%s must not exceed %d.', $this->humanize($field), $max);
            return false;
        }

        return true;
    }

    /**
     * @param string[] $allowed
     */
    public function inArray(string $field, mixed $value, array $allowed): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!in_array((string) $value, $allowed, true)) {
            $this->errors[$field] = sprintf('%s must be one of: %s.', $this->humanize($field), implode(', ', $allowed));
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function firstError(): ?string
    {
        $errors = array_values($this->errors);
        return $errors[0] ?? null;
    }

    private function humanize(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}
