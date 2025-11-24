<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MtaVersionFormat implements ValidationRule
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

        // Minimum format: MAJOR.MINOR.MAINTENANCE (e.g., 1.3.4)
        // Full format: MAJOR.MINOR.MAINTENANCE-BUILDTYPE.BUILDNUMBER (e.g., 1.6.0-9.22279.0 or 1.1.1-9.03250)
        // Accept formats:
        // - MAJOR.MINOR.MAINTENANCE (minimum required, e.g., 1.3.4)
        // - MAJOR.MINOR.MAINTENANCE-BUILDTYPE.BUILDNUMBER (full format, e.g., 1.6.0-9.22279.0)
        // - MAJOR.MINOR.MAINTENANCE-BUILDTYPE.BUILDNUMBER (without trailing .0, e.g., 1.1.1-9.03250)
        $pattern = '/^\d+\.\d+\.\d+(-\d+\.\d+(\.\d+)?)?$/';

        if (! preg_match($pattern, $value)) {
            $fail('The :attribute must be in the format MAJOR.MINOR.MAINTENANCE (e.g., 1.3.4) or MAJOR.MINOR.MAINTENANCE-BUILDTYPE.BUILDNUMBER (e.g., 1.6.0-9.22279.0 or 1.1.1-9.03250).');
        }
    }
}
