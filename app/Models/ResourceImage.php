<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'path',
        'is_display_image',
        'order',
    ];

    protected $casts = [
        'is_display_image' => 'boolean',
        'order' => 'integer',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
