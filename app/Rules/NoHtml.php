<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoHtml implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return; // Let other rules handle non-string validation
        }

        // Check for HTML tags
        if (strip_tags($value) !== $value) {
            $fail('The :attribute must not contain HTML tags.');

            return;
        }

        // Check for HTML entities that could be used for XSS
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (strip_tags($decoded) !== $decoded) {
            $fail('The :attribute must not contain HTML content.');

            return;
        }

        // Check for javascript: protocol
        if (preg_match('/javascript:/i', $value)) {
            $fail('The :attribute must not contain JavaScript code.');

            return;
        }

        // Check for event handlers (onclick, onerror, etc.)
        if (preg_match('/on\w+\s*=/i', $value)) {
            $fail('The :attribute must not contain event handlers (e.g., onclick, onerror).');

            return;
        }

        // Check for script tags (case insensitive)
        if (preg_match('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', $value)) {
            $fail('The :attribute must not contain script tags.');

            return;
        }

        // Check for iframe tags
        if (preg_match('/<iframe\b/i', $value)) {
            $fail('The :attribute must not contain iframe tags.');

            return;
        }

        // Check for style tags
        if (preg_match('/<style\b/i', $value)) {
            $fail('The :attribute must not contain style tags.');

            return;
        }
    }
}
