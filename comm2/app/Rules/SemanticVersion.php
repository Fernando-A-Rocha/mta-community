<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SemanticVersion implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        // Semantic versioning format: MAJOR.MINOR.PATCH
        // Examples: 1.0.0, 2.1.3, 0.1.0
        $pattern = '/^\d+\.\d+\.\d+$/';

        if (! preg_match($pattern, $value)) {
            $fail('The :attribute must be in semantic versioning format (MAJOR.MINOR.PATCH, e.g., 1.0.0).');
        }
    }
}
