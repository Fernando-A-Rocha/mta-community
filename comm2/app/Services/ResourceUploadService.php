<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Language;
use App\Models\Resource;
use App\Models\ResourceVersion;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;

class ResourceUploadService
{
    private const DEFAULT_VERSION = '1.0.0';

    public function __construct(
        private readonly MetaXmlParser $metaXmlParser,
        private readonly VersionValidationService $versionValidator,
        private readonly ImageOptimizationService $imageOptimizer
    ) {}

    /**
     * Process resource upload
     *
     * @param  User  $user  The user uploading the resource
     * @param  UploadedFile|string|array  $zipFile  The ZIP file (UploadedFile, file path, or array with 'path' and 'originalName')
     * @param  string|null  $version  Semantic version string (if null, will be extracted from meta.xml or default to 1.0.0)
     * @param  string  $changelog  Changelog text
     * @param  array  $tagIds  Array of tag IDs (max 5)
     * @param  array  $languageIds  Array of language IDs (required for first upload)
     * @param  array  $images  Array of uploaded images (UploadedFile instances, file paths, or arrays with 'path' and 'originalName')
     * @param  string|null  $longDescription  Long description (for first upload)
     * @param  string|null  $githubUrl  GitHub repository URL
     * @param  string|null  $forumThreadUrl  MTA Forum thread URL
     * @return resource The created or updated resource
     */
    public function upload(
        User $user,
        UploadedFile|string|array $zipFile,
        ?string $version = null,
        string $changelog = 'First public release',
        array $tagIds = [],
        array $languageIds = [],
        array $images = [],
        ?string $longDescription = null,
        ?string $githubUrl = null,
        ?string $forumThreadUrl = null
    ): Resource {
        // Convert file path/array to UploadedFile if needed
        if (! $zipFile instanceof UploadedFile) {
            if (is_array($zipFile)) {
                $zipFile = $this->createUploadedFileFromPath($zipFile['path'], $zipFile['originalName'] ?? null);
            } else {
                $zipFile = $this->createUploadedFileFromPath($zipFile);
            }
        }

        // Parse meta.xml from ZIP
        $tempZipPath = $zipFile->getRealPath();

        if (! $tempZipPath || ! file_exists($tempZipPath)) {
            throw new InvalidArgumentException('ZIP file path is invalid or file does not exist');
        }

        $metaData = $this->metaXmlParser->parse($tempZipPath);

        // Extract version from meta.xml if not provided (default to 1.0.0)
        if ($version === null) {
            $version = $metaData['version'] ?? self::DEFAULT_VERSION;
        }

        // Extract resource short name from ZIP filename (this is the actual resource identifier)
        $resourceName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);

        // Validate resource name format (alphanumeric, underscores, hyphens only)
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $resourceName)) {
            throw new InvalidArgumentException(
                "Resource name '{$resourceName}' contains invalid characters. Only letters, numbers, underscores, and hyphens are allowed."
            );
        }

        // Validate compatible gamemodes exist
        if (! empty($metaData['gamemodes'])) {
            $existingGamemodes = Resource::whereIn('name', $metaData['gamemodes'])
                ->where('category', 'gamemode')
                ->pluck('name')
                ->toArray();

            $missingGamemodes = array_diff($metaData['gamemodes'], $existingGamemodes);
            if (! empty($missingGamemodes)) {
                throw new InvalidArgumentException(
                    'The following gamemodes do not exist in the platform: '.implode(', ', $missingGamemodes)
                );
            }
        }

        return DB::transaction(function () use (
            $user,
            $zipFile,
            $version,
            $changelog,
            $tagIds,
            $languageIds,
            $images,
            $longDescription,
            $githubUrl,
            $forumThreadUrl,
            $metaData,
            $resourceName
        ) {
            // Check if resource with same name exists
            $existingResource = Resource::where('name', $resourceName)->first();

            if ($existingResource) {
                // Check if same author
                if ($existingResource->user_id !== $user->id) {
                    throw new InvalidArgumentException(
                        "A resource with the name '{$resourceName}' already exists by another author"
                    );
                }

                // Same author - this is a version update
                $this->versionValidator->validateVersionIncrement($existingResource, $version);

                // Validate changelog for updates
                if (empty($changelog) || $changelog === 'First public release') {
                    throw new InvalidArgumentException('Changelog is required for resource updates.');
                }

                $resource = $existingResource;
            } else {
                // First upload - create new resource
                if (empty($longDescription)) {
                    throw new InvalidArgumentException('Long description is required for first upload');
                }

                // Use long_name from meta.xml if provided, otherwise use resource short name
                $longName = ! empty($metaData['name']) ? $metaData['name'] : $resourceName;

                $resource = Resource::create([
                    'name' => $resourceName,
                    'long_name' => $longName,
                    'short_description' => $metaData['description'],
                    'long_description' => $longDescription,
                    'category' => $metaData['type'],
                    'user_id' => $user->id,
                    'oop_enabled' => $metaData['oop_enabled'],
                    'min_mta_version' => $this->extractMinMtaVersion($metaData['min_mta_version']),
                    'compatible_gamemodes' => $metaData['gamemodes'],
                    'github_url' => $githubUrl,
                    'forum_thread_url' => $forumThreadUrl,
                ]);

                // Set changelog to "First public release" for first upload
                $changelog = 'First public release';
            }

            // Mark previous version as not current
            ResourceVersion::where('resource_id', $resource->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            // Store ZIP file
            $zipPath = $this->storeZipFile($resource, $zipFile, $version);

            // Create new version
            $resourceVersion = ResourceVersion::create([
                'resource_id' => $resource->id,
                'version' => $version,
                'changelog' => $changelog,
                'zip_path' => $zipPath,
                'is_current' => true,
            ]);

            // Handle languages (required for first upload)
            if (! empty($languageIds)) {
                $this->syncLanguages($resource, $languageIds);
            }

            // Handle tags
            if (! empty($tagIds)) {
                $this->syncTags($resource, $tagIds);
            }

            // Handle images
            if (! empty($images)) {
                $this->storeImages($resource, $images);
            }

            // Update resource's updated_at timestamp when new version is published
            $resource->touch();

            return $resource->fresh();
        });
    }

    /**
     * Extract min_mta_version string from array
     */
    private function extractMinMtaVersion(?array $minMtaVersion): ?string
    {
        if (empty($minMtaVersion)) {
            return null;
        }

        // Prefer 'both', then 'server', then 'client'
        if (! empty($minMtaVersion['both'])) {
            return $minMtaVersion['both'];
        }

        if (! empty($minMtaVersion['server'])) {
            return $minMtaVersion['server'];
        }

        if (! empty($minMtaVersion['client'])) {
            return $minMtaVersion['client'];
        }

        return null;
    }

    /**
     * Store ZIP file in private storage
     */
    private function storeZipFile(Resource $resource, UploadedFile $zipFile, string $version): string
    {
        $directory = "resources/{$resource->id}";
        $filename = "{$version}.zip";

        $path = $zipFile->storeAs($directory, $filename, 'local');

        if ($path === false) {
            throw new RuntimeException('Failed to store ZIP file');
        }

        return $path;
    }

    /**
     * Sync languages to resource
     */
    private function syncLanguages(Resource $resource, array $languageIds): void
    {
        // Validate languages exist
        $languages = Language::whereIn('id', $languageIds)->get();

        if ($languages->count() !== count($languageIds)) {
            throw new InvalidArgumentException('One or more languages do not exist');
        }

        $resource->languages()->sync($languageIds);
    }

    /**
     * Sync tags to resource
     */
    private function syncTags(Resource $resource, array $tagIds): void
    {
        // Validate tags exist
        $tags = Tag::whereIn('id', $tagIds)->get();

        if ($tags->count() !== count($tagIds)) {
            throw new InvalidArgumentException('One or more tags do not exist');
        }

        $resource->tags()->sync($tagIds);
    }

    /**
     * Create UploadedFile from file path (for Livewire file uploads)
     */
    private function createUploadedFileFromPath(string $filePath, ?string $originalName = null): UploadedFile
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        $file = new File($filePath);
        $originalName = $originalName ?? $file->getFilename();

        return new UploadedFile(
            $file->getPathname(),
            $originalName,
            $file->getMimeType(),
            null,
            true
        );
    }

    /**
     * Store images for resource
     */
    private function storeImages(Resource $resource, array $images): void
    {
        $order = 0;

        foreach ($images as $index => $image) {
            // Convert file path/array to UploadedFile if needed
            if (! $image instanceof UploadedFile) {
                if (is_array($image)) {
                    $image = $this->createUploadedFileFromPath($image['path'], $image['originalName'] ?? null);
                } elseif (is_string($image)) {
                    $image = $this->createUploadedFileFromPath($image);
                } else {
                    continue;
                }
            }

            if (! $image->isValid()) {
                continue;
            }

            // Optimize and store image using ImageOptimizationService
            $path = $this->imageOptimizer->optimizeResourceImage($image, $resource->id);

            \App\Models\ResourceImage::create([
                'resource_id' => $resource->id,
                'path' => $path,
                'is_display_image' => $index === 0, // First image is display image
                'order' => $order++,
            ]);
        }
    }
}
