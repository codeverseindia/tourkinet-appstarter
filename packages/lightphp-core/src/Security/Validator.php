<?php
declare(strict_types=1);

namespace TourKinet\LightPHP\Security;

final class Validator
{
    public static function fields(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $constraints) {
            $value = is_scalar($data[$field] ?? null) ? (string) $data[$field] : '';
            if (($constraints['required'] ?? false) && trim($value) === '') {
                $errors[$field] = 'This field is required.';
                continue;
            }
            if ($value === '') {
                continue;
            }

            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if (isset($constraints['min']) && $length < $constraints['min']) {
                $errors[$field] = 'This field is too short.';
            } elseif (isset($constraints['max']) && $length > $constraints['max']) {
                $errors[$field] = 'This field is too long.';
            } elseif (($constraints['email'] ?? false) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $errors[$field] = 'Enter a valid email address.';
            } elseif (($constraints['phone'] ?? false) && preg_match('/^\+?[0-9][0-9\s().-]{5,28}$/D', $value) !== 1) {
                $errors[$field] = 'Enter a valid phone number.';
            } elseif (isset($constraints['allowed']) && !in_array($value, $constraints['allowed'], true)) {
                $errors[$field] = 'Select a valid option.';
            } elseif (($constraints['slug'] ?? false) && self::slug($value) === null) {
                $errors[$field] = 'Enter a valid slug.';
            } elseif (($constraints['numeric'] ?? false) && !is_numeric($value)) {
                $errors[$field] = 'Enter a numeric value.';
            } elseif (($constraints['url'] ?? false) && filter_var($value, FILTER_VALIDATE_URL) === false) {
                $errors[$field] = 'Enter a valid URL.';
            }
        }

        return $errors;
    }

    public static function slug(string $value): ?string
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,119}$/D', $value) === 1 && !str_contains($value, '..')
            ? $value
            : null;
    }
}
