<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use App\Models\MediaImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class MediaUploadService
{
    public function __construct(
        private readonly ImageOptimizationService $imageOptimizer
    ) {}

    /**
     * Check if user can upload (24h limit and public profile)
     */
    public function canUpload(User $user): bool
    {
        // Check profile visibility
        if (($user->profile_visibility ?? 'public') !== 'public') {
            return false;
        }

        // Check 24h upload limit
        $lastUpload = $user->media()
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        return ! $lastUpload;
    }

    /**
     * Get time until user can upload again
     */
    public function getTimeUntilNextUpload(User $user): ?\Carbon\Carbon
    {
        $lastUpload = $user->media()
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastUpload) {
            return null;
        }

        $nextUploadTime = $lastUpload->created_at->addDay();

        return $nextUploadTime->isFuture() ? $nextUploadTime : null;
    }

    /**
     * Extract YouTube video ID from URL
     */
    public function extractYouTubeVideoId(string $url): ?string
    {
        // Patterns for various YouTube URL formats
        $patterns = [
            '/^https?:\/\/(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/^https?:\/\/(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/^https?:\/\/(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/^https?:\/\/(?:www\.)?youtube\.com\/v\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Validate YouTube URL
     */
    public function isValidYouTubeUrl(string $url): bool
    {
        return $this->extractYouTubeVideoId($url) !== null;
    }

    /**
     * Upload media (images or video)
     *
     * @param  User  $user  The user uploading
     * @param  string  $type  'image' or 'video'
     * @param  array<UploadedFile>|null  $images  Array of image files (for image type)
     * @param  string|null  $youtubeUrl  YouTube URL (for video type)
     * @param  string  $description  Description text (max 100 chars)
     * @return Media The created media
     *
     * @throws InvalidArgumentException
     */
    public function upload(
        User $user,
        string $type,
        ?array $images = null,
        ?string $youtubeUrl = null,
        string $description = ''
    ): Media {
        if (! $this->canUpload($user)) {
            throw new InvalidArgumentException('You can only upload once per 24 hours and must have a public profile.');
        }

        if ($type === 'image') {
            if (! $images || count($images) === 0) {
                throw new InvalidArgumentException('At least one image is required for image type media.');
            }

            if (count($images) > 5) {
                throw new InvalidArgumentException('Maximum 5 images allowed.');
            }
        } elseif ($type === 'video') {
            if (! $youtubeUrl) {
                throw new InvalidArgumentException('YouTube URL is required for video type media.');
            }

            if (! $this->isValidYouTubeUrl($youtubeUrl)) {
                throw new InvalidArgumentException('Invalid YouTube URL format.');
            }
        } else {
            throw new InvalidArgumentException("Invalid media type: {$type}");
        }

        return DB::transaction(function () use ($user, $type, $images, $youtubeUrl, $description) {
            // Create media record
            $media = Media::create([
                'user_id' => $user->id,
                'type' => $type,
                'youtube_url' => $type === 'video' ? $youtubeUrl : null,
                'description' => $description,
            ]);

            // Store images if type is image
            if ($type === 'image' && $images) {
                $this->storeImages($media, $images);
            }

            return $media;
        });
    }

    /**
     * Store images for media
     */
    private function storeImages(Media $media, array $images): void
    {
        $order = 0;

        foreach ($images as $image) {
            if (! $image instanceof UploadedFile || ! $image->isValid()) {
                continue;
            }

            // Optimize and store image
            $path = $this->imageOptimizer->optimizeMediaImage($image, $media->id);

            MediaImage::create([
                'media_id' => $media->id,
                'path' => $path,
                'order' => $order++,
            ]);
        }
    }
}

