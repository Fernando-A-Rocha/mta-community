<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MtaForumUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || empty($value)) {
            return; // Let other rules handle empty/null validation
        }

        // Parse the URL
        $parsed = parse_url($value);

        // Must be a valid URL
        if ($parsed === false) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        // Must have a host
        if (empty($parsed['host'])) {
            $fail('The :attribute must be a valid MTA Forum URL.');

            return;
        }

        // Host must be forum.multitheftauto.com (case insensitive)
        if (strtolower($parsed['host']) !== 'forum.multitheftauto.com') {
            $fail('The :attribute must be from the MTA Forum (forum.multitheftauto.com).');

            return;
        }
    }
}
