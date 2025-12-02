<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImageOptimizationService
{
    /**
     * Optimize an uploaded image
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  int  $maxWidth  Maximum width in pixels
     * @param  int  $maxHeight  Maximum height in pixels
     * @param  int  $quality  JPEG quality (1-100) or PNG compression level (0-9)
     * @param  string  $directory  Storage directory path
     * @param  string|null  $filename  Optional custom filename (without extension)
     * @return string The storage path of the optimized image
     *
     * @throws ValidationException
     */
    public function optimize(
        UploadedFile $file,
        int $maxWidth = 1920,
        int $maxHeight = 1080,
        int $quality = 85,
        string $directory = 'optimized',
        ?string $filename = null
    ): string {
        $sourcePath = $file->getRealPath();
        $sourceImage = null;
        $sourceType = exif_imagetype($sourcePath);

        // Create image resource from source
        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                throw ValidationException::withMessages([
                    'image' => ['Unsupported image type. Please use JPEG, PNG, or WebP.'],
                ]);
        }

        if (! $sourceImage) {
            throw ValidationException::withMessages([
                'image' => ['Failed to process image.'],
            ]);
        }

        // Get original dimensions
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Calculate new dimensions (maintain aspect ratio)
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int) ($originalWidth * $ratio);
        $newHeight = (int) ($originalHeight * $ratio);

        // Only resize if image exceeds max dimensions
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/WebP
        $preserveTransparency = in_array($sourceType, [IMAGETYPE_PNG, IMAGETYPE_WEBP]);

        if ($preserveTransparency) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);

            // Fill with transparent color
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparent);

            // Enable alpha blending on source image for proper transparency handling
            imagealphablending($sourceImage, true);
            imagesavealpha($sourceImage, true);
        }

        // Resize image
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Disable alpha blending before saving PNG to ensure proper transparency
        if ($preserveTransparency) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        // Determine file extension and save function based on source type
        $extension = 'jpg';
        $saveFunction = null;

        switch ($sourceType) {
            case IMAGETYPE_PNG:
                $extension = 'png';
                $saveFunction = function ($image, $path) use ($quality) {
                    // PNG compression level: 0-9, convert quality (1-100) to compression level (0-9)
                    $compression = (int) (9 - (($quality / 100) * 9));
                    imagepng($image, $path, $compression);
                };
                break;
            case IMAGETYPE_WEBP:
                // Convert WebP to PNG to preserve transparency if present
                $extension = 'png';
                $saveFunction = function ($image, $path) use ($quality) {
                    $compression = (int) (9 - (($quality / 100) * 9));
                    imagepng($image, $path, $compression);
                };
                break;
            case IMAGETYPE_JPEG:
            default:
                $extension = 'jpg';
                $saveFunction = function ($image, $path) use ($quality) {
                    imagejpeg($image, $path, $quality);
                };
                break;
        }

        // Generate filename if not provided
        if (! $filename) {
            $filename = uniqid();
        }

        $filename = $filename.'.'.$extension;
        $fullPath = $directory.'/'.$filename;
        $storagePath = storage_path('app/public/'.$fullPath);

        // Ensure directory exists
        $directoryPath = dirname($storagePath);
        if (! is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Save using the appropriate function
        $saveFunction($newImage, $storagePath);

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $fullPath;
    }

    /**
     * Optimize image with specific settings for avatars
     */
    public function optimizeAvatar(UploadedFile $file, int $userId): string
    {
        return $this->optimize(
            file: $file,
            maxWidth: 500,
            maxHeight: 500,
            quality: 85,
            directory: 'avatars',
            filename: $userId.'_'.uniqid()
        );
    }

    /**
     * Optimize image with specific settings for resource images
     */
    public function optimizeResourceImage(UploadedFile $file, int $resourceId): string
    {
        return $this->optimize(
            file: $file,
            maxWidth: 1920,
            maxHeight: 1080,
            quality: 85,
            directory: "resources/{$resourceId}",
            filename: uniqid()
        );
    }

    /**
     * Optimize image with specific settings for media images
     */
    public function optimizeMediaImage(UploadedFile $file, int $mediaId): string
    {
        return $this->optimize(
            file: $file,
            maxWidth: 1920,
            maxHeight: 1080,
            quality: 85,
            directory: "media/{$mediaId}",
            filename: uniqid()
        );
    }
}
