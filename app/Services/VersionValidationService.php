<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Resource;
use InvalidArgumentException;

class VersionValidationService
{
    /**
     * Validate semantic version format
     */
    public function isValidSemanticVersion(string $version): bool
    {
        $pattern = '/^\d+\.\d+\.\d+$/';

        return preg_match($pattern, $version) === 1;
    }

    /**
     * Compare two semantic versions
     *
     * @return int Returns -1 if $version1 < $version2, 0 if equal, 1 if $version1 > $version2
     */
    public function compareVersions(string $version1, string $version2): int
    {
        if (! $this->isValidSemanticVersion($version1) || ! $this->isValidSemanticVersion($version2)) {
            throw new InvalidArgumentException('Invalid semantic version format');
        }

        $v1Parts = array_map('intval', explode('.', $version1));
        $v2Parts = array_map('intval', explode('.', $version2));

        // Compare major version
        if ($v1Parts[0] !== $v2Parts[0]) {
            return $v1Parts[0] <=> $v2Parts[0];
        }

        // Compare minor version
        if ($v1Parts[1] !== $v2Parts[1]) {
            return $v1Parts[1] <=> $v2Parts[1];
        }

        // Compare patch version
        return $v1Parts[2] <=> $v2Parts[2];
    }

    /**
     * Check if new version is greater than the latest version
     */
    public function isVersionIncremented(Resource $resource, string $newVersion): bool
    {
        $latestVersion = $resource->versions()
            ->where('is_current', true)
            ->first();

        if (! $latestVersion) {
            // First version, always valid
            return true;
        }

        $comparison = $this->compareVersions($newVersion, $latestVersion->version);

        return $comparison > 0;
    }

    /**
     * Validate that version is incremented for resource update
     */
    public function validateVersionIncrement(Resource $resource, string $newVersion): void
    {
        if (! $this->isValidSemanticVersion($newVersion)) {
            throw new InvalidArgumentException("Invalid semantic version format: {$newVersion}");
        }

        if (! $this->isVersionIncremented($resource, $newVersion)) {
            $latestVersion = $resource->versions()
                ->where('is_current', true)
                ->first();

            throw new InvalidArgumentException(
                "Version must be incremented. Latest version is {$latestVersion->version}, provided: {$newVersion}"
            );
        }
    }
}
