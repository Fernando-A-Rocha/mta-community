<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'youtube_url',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(MediaImage::class)->orderBy('order');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MediaReaction::class);
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', 'image');
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', 'video');
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function reactionCount(): int
    {
        return $this->reactions()->count();
    }

    public function userReaction(?User $user): ?MediaReaction
    {
        if (! $user) {
            return null;
        }

        return $this->reactions()->where('user_id', $user->id)->first();
    }

    /**
     * Get reaction counts grouped by emoji
     */
    public function getReactionCountsAttribute(): array
    {
        // Use loaded relations if available, otherwise query
        if ($this->relationLoaded('reactions')) {
            $counts = [];
            foreach ($this->reactions as $reaction) {
                $emoji = $reaction->emoji;
                $counts[$emoji] = ($counts[$emoji] ?? 0) + 1;
            }

            return $counts;
        }

        return $this->reactions()
            ->selectRaw('emoji, count(*) as count')
            ->groupBy('emoji')
            ->pluck('count', 'emoji')
            ->toArray();
    }
}
