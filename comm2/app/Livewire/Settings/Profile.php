<?php

namespace App\Livewire\Settings;

use App\Data\ProfileFavorites;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public string $profile_visibility = 'public';

    public ?string $favorite_city = null;

    public ?string $favorite_vehicle = null;

    public ?string $favorite_character = null;

    public ?string $favorite_gang = null;

    public ?string $favorite_weapon = null;

    public ?string $favorite_radio_station = null;

    public $avatar = null;

    public $avatarPreview = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->profile_visibility = $user->profile_visibility ?? 'public';
        $this->favorite_city = $user->favorite_city;
        $this->favorite_vehicle = $user->favorite_vehicle;
        $this->favorite_character = $user->favorite_character;
        $this->favorite_gang = $user->favorite_gang;
        $this->favorite_weapon = $user->favorite_weapon;
        $this->favorite_radio_station = $user->favorite_radio_station;
        $this->avatarPreview = $user->avatarUrl();
    }

    /**
     * Updated avatar property
     */
    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:512'], // 500KB = 512KB, no GIF
        ]);

        if ($this->avatar) {
            $this->validateImageType($this->avatar);
            $this->avatarPreview = $this->avatar->temporaryUrl();
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'profile_visibility' => ['required', 'string', Rule::in(['public', 'private'])],
            'favorite_city' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::cities()))],
            'favorite_vehicle' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::vehicles()))],
            'favorite_character' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::characters()))],
            'favorite_gang' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::gangs()))],
            'favorite_weapon' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::weapons()))],
            'favorite_radio_station' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::radioStations()))],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:512'], // 512KB (500KB limit with some buffer), no GIF
        ]);

        // Convert empty strings to null
        $validated = array_map(fn ($value) => $value === '' ? null : $value, $validated);

        if (($validated['profile_visibility'] ?? 'public') === 'private' && $user->resources()->exists()) {
            throw ValidationException::withMessages([
                'profile_visibility' => [__('You must keep your profile public while you host published resources.')],
            ]);
        }

        // Handle avatar upload
        if ($this->avatar) {
            $this->validateImageType($this->avatar);

            // Delete old avatar if exists
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // Resize and store new avatar
            $avatarPath = $this->storeAvatar($this->avatar, $user->id);
            $user->avatar_path = $avatarPath;
        }

        // Update other profile fields
        $user->profile_visibility = $validated['profile_visibility'];
        $user->favorite_city = $validated['favorite_city'];
        $user->favorite_vehicle = $validated['favorite_vehicle'];
        $user->favorite_character = $validated['favorite_character'];
        $user->favorite_gang = $validated['favorite_gang'];
        $user->favorite_weapon = $validated['favorite_weapon'];
        $user->favorite_radio_station = $validated['favorite_radio_station'];

        $user->save();

        // Reset avatar property
        $this->avatar = null;
        $this->avatarPreview = $user->avatarUrl();

        $this->dispatch('profile-updated');
    }

    /**
     * Delete the user's avatar
     */
    public function deleteAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = null;
        $user->save();

        $this->avatarPreview = null;
        $this->avatar = null;

        $this->dispatch('avatar-deleted');
    }

    /**
     * Validate that the image is not a GIF
     */
    private function validateImageType($file): void
    {
        $sourcePath = $file->getRealPath();
        $sourceType = exif_imagetype($sourcePath);

        if ($sourceType === IMAGETYPE_GIF) {
            throw ValidationException::withMessages([
                'avatar' => ['GIF files are not allowed. Please use JPEG, PNG, or WebP.'],
            ]);
        }
    }

    /**
     * Store and resize avatar image
     */
    private function storeAvatar($file, int $userId): string
    {
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
                    'avatar' => ['Unsupported image type. Please use JPEG, PNG, or WebP.'],
                ]);
        }

        if (! $sourceImage) {
            throw ValidationException::withMessages([
                'avatar' => ['Failed to process image.'],
            ]);
        }

        // Get original dimensions
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Calculate new dimensions (max 500x500, maintain aspect ratio)
        $maxSize = 500;
        $ratio = min($maxSize / $originalWidth, $maxSize / $originalHeight);
        $newWidth = (int) ($originalWidth * $ratio);
        $newHeight = (int) ($originalHeight * $ratio);

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/WebP
        $preserveTransparency = in_array($sourceType, [IMAGETYPE_PNG, IMAGETYPE_WEBP]);

        if ($preserveTransparency) {
            // Enable alpha blending and save alpha channel for destination
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
        // Preserve PNG format to maintain transparency
        $extension = 'jpg';
        $saveFunction = null;

        switch ($sourceType) {
            case IMAGETYPE_PNG:
                $extension = 'png';
                $saveFunction = function ($image, $path) {
                    // Save PNG with compression level 6 (good balance between size and speed)
                    imagepng($image, $path, 6);
                };
                break;
            case IMAGETYPE_WEBP:
                // Convert WebP to PNG to preserve transparency if present
                $extension = 'png';
                $saveFunction = function ($image, $path) {
                    imagepng($image, $path, 6);
                };
                break;
            case IMAGETYPE_JPEG:
            default:
                $extension = 'jpg';
                $saveFunction = function ($image, $path) {
                    // Save with quality 85 for good balance between size and quality
                    imagejpeg($image, $path, 85);
                };
                break;
        }

        $filename = 'avatars/'.$userId.'_'.uniqid().'.'.$extension;
        $path = storage_path('app/public/'.$filename);

        // Ensure directory exists
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Save using the appropriate function
        $saveFunction($newImage, $path);

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $filename;
    }

    public function render()
    {
        return view('livewire.settings.profile');
    }
}
