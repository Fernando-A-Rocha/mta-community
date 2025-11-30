<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'resource_language');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ResourceDownload::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Get the unique download count (all time, one per user/IP).
     * For authenticated users, we count by user_id. For guests, we count by IP.
     */
    public function getUniqueDownloadsCountAttribute(): int
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

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /**
     * Get the rating count for the resource.
     */
    public function getRatingCountAttribute(): int
    {
        return $this->ratings()->count();
    }

    /**
     * Check if the latest release is verified.
     */
    public function isLatestVersionVerified(): bool
    {
        return $this->currentVersion?->is_verified ?? false;
    }
}
