<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_id',
        'user_id',
        'emoji',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Valid emoji types
     */
    public const VALID_EMOJIS = [
        '❤️', // Like
        '😂', // Laugh
        '😮', // Wow
        '🔥', // Fire
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if emoji is valid
     */
    public static function isValidEmoji(string $emoji): bool
    {
        return in_array($emoji, self::VALID_EMOJIS, true);
    }
}
