<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GitHubUrl implements ValidationRule
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
            $fail('The :attribute must be a valid GitHub URL.');

            return;
        }

        // Host must be github.com (case insensitive)
        if (strtolower($parsed['host']) !== 'github.com') {
            $fail('The :attribute must be a GitHub URL (github.com).');

            return;
        }

        // Must have a path with at least username/repo
        if (empty($parsed['path'])) {
            $fail('The :attribute must include a repository path (e.g., github.com/username/repo).');

            return;
        }

        // Path should be in format /username/repo (may have additional path segments)
        $pathParts = array_filter(explode('/', trim($parsed['path'], '/')));
        if (count($pathParts) < 2) {
            $fail('The :attribute must include both username and repository (e.g., github.com/username/repo).');

            return;
        }

        // Username and repo should be alphanumeric, hyphens, underscores, dots
        foreach (array_slice($pathParts, 0, 2) as $part) {
            if (! preg_match('/^[a-zA-Z0-9._-]+$/', $part)) {
                $fail('The :attribute contains invalid characters in the username or repository name.');

                return;
            }
        }
    }
}
