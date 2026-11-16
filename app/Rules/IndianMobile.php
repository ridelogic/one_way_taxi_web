<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Indian mobile numbers: 10 digits starting 6–9, optionally prefixed with +91, 91 or 0.
 * Spaces, hyphens and brackets are ignored.
 */
class IndianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize($value) === null) {
            $fail('Please enter a valid 10-digit Indian mobile number.');
        }
    }

    /** Returns the number as +91XXXXXXXXXX, or null when it is not a valid Indian mobile number. */
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if (! preg_match('/^\+?[0-9\s\-()]+$/', $raw)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[6-9]\d{9}$/', $digits) ? '+91'.$digits : null;
    }
}
