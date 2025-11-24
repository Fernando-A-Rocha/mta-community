<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'version',
        'changelog',
        'zip_path',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
