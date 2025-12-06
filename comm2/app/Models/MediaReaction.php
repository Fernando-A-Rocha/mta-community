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
        // Custom PNG reactions
        'custom:mreow',
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

    /**
     * Check if a reaction is a custom PNG image reaction
     */
    public static function isCustomReaction(string $emoji): bool
    {
        return str_starts_with($emoji, 'custom:');
    }

    /**
     * Get the image filename for a custom reaction
     * Returns null if not a custom reaction
     */
    public static function getCustomReactionImage(string $emoji): ?string
    {
        if (! self::isCustomReaction($emoji)) {
            return null;
        }

        $reactionName = str_replace('custom:', '', $emoji);

        return "images/reactions/{$reactionName}.png";
    }
}
