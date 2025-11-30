<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EnglishOnly implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        // Reject characters outside standard ASCII printable set
        if (preg_match('/[^\x09\x0A\x0D\x20-\x7E]/u', $value)) {
            $fail(__('The :attribute must be written in English (ASCII characters only).'));
            return;
        }
    }
}
