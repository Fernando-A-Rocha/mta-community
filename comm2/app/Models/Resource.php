<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'long_name',
        'short_description',
        'long_description',
        'category',
        'user_id',
        'downloads_count',
        'oop_enabled',
        'github_url',
        'forum_thread_url',
        'min_mta_version',
        'compatible_gamemodes',
        'is_disabled',
    ];

    protected $casts = [
        'compatible_gamemodes' => 'array',
        'oop_enabled' => 'boolean',
        'is_disabled' => 'boolean',
        'downloads_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ResourceVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(ResourceVersion::class)->where('is_current', true);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ResourceRating::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ResourceImage::class)->orderBy('order');
    }

    public function displayImage(): HasOne
    {
        return $this->hasOne(ResourceImage::class)->where('is_display_image', true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ResourceDownload::class);
    }

    /**
     * Get the unique download count (one per user/IP per 24 hours).
     * This counts distinct user/IP combinations that have downloaded in the last 24 hours.
     * For authenticated users, we count by user_id. For guests, we count by IP.
     */
    public function getUniqueDownloadsCountAttribute(): int
    {
        $twentyFourHoursAgo = now()->subDay();

        // Get all downloads in the past 24 hours
        $recentDownloads = $this->downloads()
            ->where('created_at', '>=', $twentyFourHoursAgo)
            ->get();

        // Count unique by user_id (for authenticated) or ip_address (for guests)
        $uniqueIdentifiers = $recentDownloads
            ->map(function ($download) {
                // Use user_id if available, otherwise use IP address
                return $download->user_id ?? $download->ip_address;
            })
            ->unique()
            ->count();

        return $uniqueIdentifiers;
    }

    /**
     * Get the total unique download count (all time, one per user/IP).
     */
    public function getTotalDownloadsCountAttribute(): int
    {
        $userDownloads = $this->downloads()
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $ipDownloads = $this->downloads()
            ->whereNull('user_id')
            ->distinct('ip_address')
            ->count('ip_address');

        return $userDownloads + $ipDownloads;
    }

    /**
     * Check if a user/IP has downloaded this resource in the past 24 hours.
     */
    public function hasDownloadedRecently(?int $userId, string $ipAddress): bool
    {
        $twentyFourHoursAgo = now()->subDay();

        $query = $this->downloads()
            ->where('created_at', '>=', $twentyFourHoursAgo);

        // Check by user_id if authenticated, otherwise by IP
        if ($userId) {
            return $query->where('user_id', $userId)->exists();
        }

        return $query->where('ip_address', $ipAddress)
            ->whereNull('user_id')
            ->exists();
    }

    /**
     * Get the display name for the resource.
     * Format: "short name (long name)" if long_name exists, otherwise just "short name"
     */
    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->long_name) && $this->long_name !== $this->name) {
            return "{$this->name} ({$this->long_name})";
        }

        return $this->name;
    }

    /**
     * Get the average rating for the resource.
     */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->ratings()->avg('rating');

        return $avg !== null ? (float) round($avg, 2) : null;
    }

    /**
     * Get the rating count for the resource.
     */
    public function getRatingCountAttribute(): int
    {
        return $this->ratings()->count();
    }
}
